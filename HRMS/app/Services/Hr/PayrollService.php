<?php

namespace App\Services\Hr;

use App\Jobs\PostJournalToAccounts;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\PayrollRun;
use App\Models\SalaryStructure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(
        private readonly HrConfig $config = new HrConfig(),
        private readonly LeaveService $leave = new LeaveService(),
    ) {
    }

    public function openRun(string $period): PayrollRun
    {
        $run = PayrollRun::firstOrCreate(
            ['period' => $period],
            ['status' => 'draft', 'pay_date' => Carbon::parse($period . '-01')->day($this->config->payDay())],
        );
        if (! $run->isEditable()) {
            return $run;
        }

        $workingDays = Carbon::parse($period . '-01')->daysInMonth;

        Employee::active()->with('currentStructure')->get()->each(function (Employee $emp) use ($run, $workingDays, $period) {
            $structure = $emp->currentStructure;
            if (! $structure) {
                return;
            }
            $lop = $this->leave->unpaidDaysInPeriod($emp, $period);
            $paidDays = max(0, $workingDays - $lop);

            Payslip::updateOrCreate(
                ['payroll_run_id' => $run->id, 'employee_id' => $emp->id],
                $this->computePayslip($emp, $structure, $paidDays, $workingDays) + [
                    'working_days' => $workingDays,
                    'paid_days' => $paidDays,
                    'lop_days' => $lop,
                    'snapshot' => [
                        'name' => $emp->name, 'code' => $emp->employee_code, 'pan' => $emp->pan,
                        'bank_account_no' => $emp->bank_account_no, 'bank_ifsc' => $emp->bank_ifsc,
                        'pf_uan' => $emp->pf_uan, 'esi_ic_number' => $emp->esi_ic_number,
                        'department' => $emp->department?->name, 'designation' => $emp->designation?->name,
                    ],
                ],
            );
        });

        return $this->refreshTotals($run);
    }

    public function computePayslip(Employee $emp, SalaryStructure $structure, float $paidDays, int $workingDays): array
    {
        $ratio = $workingDays > 0 ? $paidDays / $workingDays : 1.0;

        $fullBasic = 0.0;
        foreach ($structure->earnings ?? [] as $c) {
            if (($c['calc'] ?? 'flat') === 'flat' && strtolower($c['code'] ?? '') === 'basic') {
                $fullBasic = (float) ($c['value'] ?? 0);
            }
        }

        $earnings = [];
        foreach ($structure->earnings ?? [] as $c) {
            $code = $c['code'] ?? 'earn';
            $full = ($c['calc'] ?? 'flat') === 'pct_basic'
                ? round($fullBasic * (float) ($c['value'] ?? 0) / 100, 2)
                : (float) ($c['value'] ?? 0);
            $earnings[] = ['code' => $code, 'label' => $c['label'] ?? ucfirst($code), 'amount' => round($full * $ratio, 2), 'full' => round($full, 2)];
        }
        $gross = round(array_sum(array_column($earnings, 'amount')), 2);
        $basic = round($fullBasic * $ratio, 2);

        $pfBase = min($basic, $this->config->pfWageCeiling());
        $pfEe = round($pfBase * $this->config->pfEmployeeRate() / 100, 2);
        $pfEr = round($pfBase * $this->config->pfEmployerRate() / 100, 2);

        $esiEe = $esiEr = 0.0;
        if ($gross > 0 && $gross <= $this->config->esiWageCeiling()) {
            $esiEe = round($gross * $this->config->esiEmployeeRate() / 100, 2);
            $esiEr = round($gross * $this->config->esiEmployerRate() / 100, 2);
        }
        $pt = round($this->config->ptForGross($gross), 2);
        $tds = match ($this->config->tds192Mode()) {
            'flat' => round($gross * $this->config->tds192FlatRate() / 100, 2),
            'projected' => $this->config->projectedMonthlyTds(max(0, $gross - $pfEe)),
            default => 0.0,
        };

        $deductions = array_values(array_filter([
            $pfEe > 0 ? ['code' => 'pf', 'label' => 'Provident Fund', 'amount' => $pfEe] : null,
            $esiEe > 0 ? ['code' => 'esi', 'label' => 'ESI', 'amount' => $esiEe] : null,
            $pt > 0 ? ['code' => 'pt', 'label' => 'Professional Tax', 'amount' => $pt] : null,
            $tds > 0 ? ['code' => 'tds', 'label' => 'TDS (Sec 192)', 'amount' => $tds] : null,
        ]));
        foreach ($structure->deductions ?? [] as $d) {
            $deductions[] = ['code' => $d['code'] ?? 'ded', 'label' => $d['label'] ?? 'Deduction', 'amount' => round((float) ($d['value'] ?? 0) * $ratio, 2)];
        }
        $totalDed = round(array_sum(array_column($deductions, 'amount')), 2);

        return [
            'earnings' => $earnings,
            'deductions' => $deductions,
            'gross' => $gross,
            'total_deductions' => $totalDed,
            'net_pay' => round($gross - $totalDed, 2),
            'employer_pf' => $pfEr,
            'employer_esi' => $esiEr,
            'employer_cost' => round($gross + $pfEr + $esiEr, 2),
            'tds_192' => $tds,
        ];
    }

    public function recalculate(PayrollRun $run): PayrollRun
    {
        abort_unless($run->isEditable(), 422, 'Run is locked.');
        $workingDays = Carbon::parse($run->period . '-01')->daysInMonth;

        $run->payslips()->with('employee.currentStructure')->get()->each(function (Payslip $slip) use ($workingDays, $run) {
            $emp = $slip->employee;
            $structure = $emp?->currentStructure;
            if (! $structure) {
                return;
            }
            $lop = $this->leave->unpaidDaysInPeriod($emp, $run->period);
            $paidDays = max(0, $workingDays - $lop);
            $slip->fill($this->computePayslip($emp, $structure, $paidDays, $workingDays) + [
                'working_days' => $workingDays, 'paid_days' => $paidDays, 'lop_days' => $lop,
            ])->save();
        });

        return $this->refreshTotals($run);
    }

    public function refreshTotals(PayrollRun $run): PayrollRun
    {
        $slips = $run->payslips()->get();
        $sum = fn (string $col) => round((float) $slips->sum($col), 2);
        $dedSum = fn (string $code) => round($slips->sum(fn ($s) => collect($s->deductions)->firstWhere('code', $code)['amount'] ?? 0), 2);

        $run->forceFill(['totals' => [
            'headcount' => $slips->count(),
            'gross' => $sum('gross'),
            'deductions' => $sum('total_deductions'),
            'net' => $sum('net_pay'),
            'pf_ee' => $dedSum('pf'),
            'pf_er' => $sum('employer_pf'),
            'esi_ee' => $dedSum('esi'),
            'esi_er' => $sum('employer_esi'),
            'pt' => $dedSum('pt'),
            'tds_192' => $sum('tds_192'),
            'employer_cost' => $sum('employer_cost'),
        ]])->save();

        return $run->refresh();
    }

    public function approve(PayrollRun $run): PayrollRun
    {
        abort_unless($run->isEditable(), 422, 'Run already approved.');
        abort_if($run->payslips()->count() === 0, 422, 'No payslips to approve.');

        $this->refreshTotals($run);
        $run->forceFill(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()])->save();

        $this->pushAccrual($run->refresh());

        return $run->refresh();
    }

    public function markPaid(PayrollRun $run, ?string $payDate = null): PayrollRun
    {
        abort_unless(in_array($run->status, ['approved', 'posted'], true), 422, 'Approve the run first.');

        $run->forceFill([
            'status' => 'paid',
            'pay_date' => $payDate ? Carbon::parse($payDate) : ($run->pay_date ?: now()),
            'paid_by' => auth()->id(),
            'paid_at' => now(),
        ])->save();

        $this->pushPayment($run->refresh());

        return $run->refresh();
    }

    public function cancel(PayrollRun $run): PayrollRun
    {
        abort_unless($run->isEditable(), 422, 'Only draft runs can be cancelled.');
        DB::transaction(function () use ($run) {
            $run->payslips()->delete();
            $run->forceFill(['status' => 'cancelled'])->save();
        });

        return $run;
    }

    /* ---------------- ledger push ---------------- */

    private function pushAccrual(PayrollRun $run): void
    {
        $t = $run->totals ?? [];
        $gross = round((float) ($t['gross'] ?? 0), 2);
        if ($gross <= 0) {
            return;
        }
        $net = round((float) ($t['net'] ?? 0), 2);
        $tds = round((float) ($t['tds_192'] ?? 0), 2);
        $pfEe = round((float) ($t['pf_ee'] ?? 0), 2);
        $pfEr = round((float) ($t['pf_er'] ?? 0), 2);
        $esiEe = round((float) ($t['esi_ee'] ?? 0), 2);
        $esiEr = round((float) ($t['esi_er'] ?? 0), 2);
        $pt = round((float) ($t['pt'] ?? 0), 2);

        $lines = [
            ['account' => '5100', 'debit' => $gross, 'credit' => 0, 'memo' => 'Gross salary ' . $run->period],
            ['account' => '5110', 'debit' => round($pfEr + $esiEr, 2), 'credit' => 0, 'memo' => 'Employer PF/ESI ' . $run->period],
            ['account' => '2015', 'debit' => 0, 'credit' => $net, 'memo' => 'Net salary payable'],
            ['account' => '2370', 'debit' => 0, 'credit' => $tds, 'memo' => 'TDS u/s 192'],
            ['account' => '2380', 'debit' => 0, 'credit' => round($pfEe + $pfEr, 2), 'memo' => 'PF payable (EE+ER)'],
            ['account' => '2390', 'debit' => 0, 'credit' => round($esiEe + $esiEr + $pt, 2), 'memo' => 'ESI + PT payable'],
        ];

        PostJournalToAccounts::dispatch(
            $run->id, 'payroll_accrual', $lines,
            Carbon::parse($run->period . '-01')->endOfMonth()->toDateString(),
            'Payroll ' . $run->period,
        );
    }

    private function pushPayment(PayrollRun $run): void
    {
        $net = round((float) ($run->totals['net'] ?? 0), 2);
        if ($net <= 0) {
            return;
        }
        $lines = [
            ['account' => '2015', 'debit' => $net, 'credit' => 0, 'memo' => 'Salary disbursed ' . $run->period],
            ['account' => $this->config->paymentAccountCode(), 'debit' => 0, 'credit' => $net, 'memo' => 'Bank — salary ' . $run->period],
        ];

        PostJournalToAccounts::dispatch(
            $run->id, 'payroll_payment', $lines,
            ($run->pay_date ?: now())->toDateString(),
            'Payroll payment ' . $run->period,
        );
    }

    public function leaveLopDays(Employee $emp, string $period): int
    {
        return (int) $this->leave->unpaidDaysInPeriod($emp, $period);
    }
}

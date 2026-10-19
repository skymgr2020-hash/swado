<?php

namespace App\Console\Commands;

use App\Jobs\PostJournalToAccounts;
use App\Models\PayrollRun;
use App\Services\Hr\PayrollService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Re-pushes any approved/paid payroll run whose Accounts journal reference is
 * still blank (Accounts was down when the run was approved/paid).
 */
class HrReconcileJournals extends Command
{
    protected $signature = 'hr:reconcile-journals';

    protected $description = 'Re-queue payroll journals to the Accounts app for runs with a missing reference';

    public function handle(PayrollService $payroll): int
    {
        $pushed = 0;

        PayrollRun::whereIn('status', ['approved', 'paid', 'posted'])
            ->where(fn ($q) => $q->whereNull('accrual_journal_ref')
                ->orWhere(fn ($w) => $w->whereIn('status', ['paid', 'posted'])->whereNull('payment_journal_ref')))
            ->get()
            ->each(function (PayrollRun $run) use (&$pushed) {
                $t = $run->totals ?? [];
                if (! $run->accrual_journal_ref && (float) ($t['gross'] ?? 0) > 0) {
                    PostJournalToAccounts::dispatch($run->id, 'payroll_accrual', $this->accrualLines($run),
                        Carbon::parse($run->period . '-01')->endOfMonth()->toDateString(), 'Payroll ' . $run->period);
                    $pushed++;
                }
                if (in_array($run->status, ['paid', 'posted'], true) && ! $run->payment_journal_ref && (float) ($t['net'] ?? 0) > 0) {
                    PostJournalToAccounts::dispatch($run->id, 'payroll_payment', $this->paymentLines($run),
                        ($run->pay_date ?: now())->toDateString(), 'Payroll payment ' . $run->period);
                    $pushed++;
                }
            });

        $this->info("Re-queued {$pushed} payroll journal(s).");

        return self::SUCCESS;
    }

    private function accrualLines(PayrollRun $run): array
    {
        $t = $run->totals ?? [];
        $g = fn ($k) => round((float) ($t[$k] ?? 0), 2);

        return [
            ['account' => '5100', 'debit' => $g('gross'), 'credit' => 0, 'memo' => 'Gross salary ' . $run->period],
            ['account' => '5110', 'debit' => round($g('pf_er') + $g('esi_er'), 2), 'credit' => 0, 'memo' => 'Employer PF/ESI ' . $run->period],
            ['account' => '2015', 'debit' => 0, 'credit' => $g('net'), 'memo' => 'Net salary payable'],
            ['account' => '2370', 'debit' => 0, 'credit' => $g('tds_192'), 'memo' => 'TDS u/s 192'],
            ['account' => '2380', 'debit' => 0, 'credit' => round($g('pf_ee') + $g('pf_er'), 2), 'memo' => 'PF payable (EE+ER)'],
            ['account' => '2390', 'debit' => 0, 'credit' => round($g('esi_ee') + $g('esi_er') + $g('pt'), 2), 'memo' => 'ESI + PT payable'],
        ];
    }

    private function paymentLines(PayrollRun $run): array
    {
        $net = round((float) ($run->totals['net'] ?? 0), 2);
        $acct = (new \App\Services\Hr\HrConfig())->paymentAccountCode();

        return [
            ['account' => '2015', 'debit' => $net, 'credit' => 0, 'memo' => 'Salary disbursed ' . $run->period],
            ['account' => $acct, 'debit' => 0, 'credit' => $net, 'memo' => 'Bank — salary ' . $run->period],
        ];
    }
}

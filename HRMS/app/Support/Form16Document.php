<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\HrSetting;
use App\Models\Payslip;
use App\Services\Hr\HrConfig;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfInstance;
use Illuminate\Support\Carbon;

/**
 * Form 16 Part B (salary detail + tax computation) for one employee / FY.
 * Built from the approved payslips of the twelve months in the FY.
 */
class Form16Document
{
    public static function data(Employee $employee, string $fy): array
    {
        [$y1] = explode('-', $fy);
        $start = Carbon::createFromDate((int) $y1, 4, 1)->startOfMonth();
        $months = collect(range(0, 11))->map(fn ($m) => $start->copy()->addMonths($m)->format('Y-m'))->all();

        $slips = Payslip::with('run')
            ->where('employee_id', $employee->id)
            ->whereHas('run', fn ($q) => $q->whereIn('period', $months)->whereIn('status', ['approved', 'paid', 'posted']))
            ->get();

        $sumDed = fn ($c) => round($slips->sum(fn ($s) => collect($s->deductions)->firstWhere('code', $c)['amount'] ?? 0), 2);

        $gross = round($slips->sum('gross'), 2);
        $pt = $sumDed('pt');
        $pf = $sumDed('pf');
        $tds = round($slips->sum('tds_192'), 2);
        $stdDeduction = min($gross, 75000);
        $taxable = round(max(0, $gross - $stdDeduction - $pt - $pf), 2);

        return [
            'employee' => $employee->loadMissing('designation'),
            'fy' => $fy,
            'assessment_year' => ((int) $y1 + 1) . '-' . str_pad((string) (((int) $y1 + 2) % 100), 2, '0', STR_PAD_LEFT),
            'employer' => [
                'name' => HrSetting::get('employer_legal_name', config('app.name')),
                'pan' => HrSetting::get('employer_pan'),
                'tan' => HrSetting::get('employer_tan'),
            ],
            'months' => $slips->sortBy('run.period')->values(),
            'totals' => [
                'gross' => $gross,
                'std_deduction' => $stdDeduction,
                'pt' => $pt,
                'pf' => $pf,
                'taxable' => $taxable,
                'tds' => $tds,
            ],
            'regime' => strtoupper($employee->tax_regime ?: (new HrConfig())->defaultTaxRegime()),
            'symbol' => HrSetting::get('currency_symbol', '₹'),
        ];
    }

    public static function pdf(Employee $employee, string $fy): PdfInstance
    {
        return Pdf::loadView('documents.form16', self::data($employee, $fy))->setPaper('a4');
    }
}

<?php

namespace App\Exports;

use App\Exports\Concerns\WithStatutoryStyling;
use App\Models\Employee;
use App\Models\Payslip;
use App\Services\Hr\HrConfig;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Form 24Q Annexure II — year-to-date salary + TDS u/s 192 per deductee for a
 * financial year (e.g. "2026-27"). One row per employee.
 */
class Form24qExport implements FromCollection, WithHeadings, WithTitle, WithEvents
{
    use WithStatutoryStyling;

    private array $months;

    public function __construct(private readonly string $fy)
    {
        [$y1] = explode('-', $fy);
        $start = Carbon::createFromDate((int) $y1, 4, 1)->startOfMonth();
        $this->months = collect(range(0, 11))->map(fn ($m) => $start->copy()->addMonths($m)->format('Y-m'))->all();
    }

    public function title(): string
    {
        return 'Form 24Q ' . $this->fy;
    }

    protected function statutoryTitle(): string
    {
        return 'FORM 24Q — ANNEXURE II  ·  Details of salary paid and tax deducted  ·  FY ' . $this->fy;
    }

    public function headings(): array
    {
        return ['S.No', 'PAN', 'Employee Name', 'Designation', 'Gross Salary', 'Deduction u/s 16 (PT)', 'Deductions Ch. VI-A (PF)', 'Taxable Income', 'Tax Deducted (192)', 'Regime'];
    }

    public function collection(): Collection
    {
        $slips = Payslip::with('employee.designation')
            ->whereHas('run', fn ($q) => $q->whereIn('period', $this->months)->whereIn('status', ['approved', 'paid', 'posted']))
            ->get()
            ->groupBy('employee_id');

        return $slips->values()->map(function (Collection $rows, $i) {
            /** @var Employee $e */
            $e = $rows->first()->employee;
            $gross = round($rows->sum('gross'), 2);
            $ded = fn ($c) => round($rows->sum(fn ($s) => collect($s->deductions)->firstWhere('code', $c)['amount'] ?? 0), 2);
            $pt = $ded('pt');
            $pf = $ded('pf');
            $tds = round($rows->sum('tds_192'), 2);

            return [
                $i + 1,
                $e->pan,
                $e->name,
                $e->designation?->name,
                $gross,
                $pt,
                $pf,
                round(max(0, $gross - $pt - $pf), 2),
                $tds,
                strtoupper($e->tax_regime ?: (new HrConfig())->defaultTaxRegime()),
            ];
        });
    }
}

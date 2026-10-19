<?php

namespace App\Exports;

use App\Exports\Concerns\WithStatutoryStyling;
use App\Models\PayrollRun;
use App\Services\Hr\HrConfig;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * PF (ECR), ESI or PT contribution worksheet for a single payroll run.
 * $kind ∈ pf | esi | pt.
 */
class StatutoryContributionExport implements FromCollection, WithHeadings, WithTitle, WithEvents
{
    use \App\Exports\Concerns\WithStatutoryStyling;

    private HrConfig $cfg;

    public function __construct(private readonly PayrollRun $run, private readonly string $kind)
    {
        $this->cfg = new HrConfig();
    }

    public function title(): string
    {
        return strtoupper($this->kind) . ' ' . $this->run->period;
    }

    protected function statutoryTitle(): string
    {
        return match ($this->kind) {
            'pf' => 'EPF — ELECTRONIC CHALLAN CUM RETURN (ECR)  ·  ' . $this->run->period,
            'esi' => 'ESI — MONTHLY CONTRIBUTION STATEMENT  ·  ' . $this->run->period,
            default => 'PROFESSIONAL TAX — MONTHLY STATEMENT  ·  ' . $this->run->period,
        };
    }

    public function headings(): array
    {
        return match ($this->kind) {
            'pf' => ['UAN', 'Member Name', 'Gross Wages', 'EPF Wages', 'EPS Wages', 'EE Share (12%)', 'EPS (8.33%)', 'ER Diff (3.67%)', 'NCP Days'],
            'esi' => ['IP Number', 'IP Name', 'No. of Days', 'Total Wages', 'EE Contribution (0.75%)', 'ER Contribution (3.25%)'],
            default => ['Emp Code', 'Employee Name', 'Gross', 'PT Deducted'],
        };
    }

    public function collection(): Collection
    {
        $slips = $this->run->payslips()->with('employee')->get();
        $ded = fn ($s, $c) => collect($s->deductions)->firstWhere('code', $c)['amount'] ?? 0;

        return $slips->map(function ($s) use ($ded) {
            $e = $s->employee;
            $basicFull = collect($s->earnings)->firstWhere('code', 'basic')['amount'] ?? 0;
            $pfWage = min((float) $basicFull, $this->cfg->pfWageCeiling());

            return match ($this->kind) {
                'pf' => [
                    $e->pf_uan, $e->name, (float) $s->gross, $pfWage, $pfWage,
                    $ded($s, 'pf'),
                    round($pfWage * $this->cfg->epsRate() / 100, 2),
                    round($ded($s, 'pf') - $pfWage * $this->cfg->epsRate() / 100, 2),
                    (float) $s->lop_days,
                ],
                'esi' => [
                    $e->esi_ic_number, $e->name, (int) $s->paid_days, (float) $s->gross,
                    $ded($s, 'esi'), (float) $s->employer_esi,
                ],
                default => [$e->employee_code, $e->name, (float) $s->gross, $ded($s, 'pt')],
            };
        });
    }
}

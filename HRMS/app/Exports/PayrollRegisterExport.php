<?php

namespace App\Exports;

use App\Exports\Concerns\WithStatutoryStyling;
use App\Models\PayrollRun;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PayrollRegisterExport implements FromCollection, WithHeadings, WithTitle, WithEvents
{
    use WithStatutoryStyling;

    public function __construct(private readonly PayrollRun $run)
    {
    }

    public function title(): string
    {
        return 'Payroll ' . $this->run->period;
    }

    protected function statutoryTitle(): string
    {
        return 'PAYROLL REGISTER  ·  ' . $this->run->period . '  ·  status: ' . ucfirst($this->run->status);
    }

    public function headings(): array
    {
        return ['Emp Code', 'Name', 'Dept', 'Designation', 'Paid Days', 'LOP', 'Gross', 'PF (EE)', 'ESI (EE)', 'PT', 'TDS 192', 'Other Ded.', 'Net Pay', 'Employer PF', 'Employer ESI', 'CTC Cost'];
    }

    public function collection(): Collection
    {
        return $this->run->payslips()->with('employee.department', 'employee.designation')->get()->map(function ($s) {
            $ded = fn ($c) => collect($s->deductions)->firstWhere('code', $c)['amount'] ?? 0;
            $other = collect($s->deductions)->whereNotIn('code', ['pf', 'esi', 'pt', 'tds'])->sum('amount');

            return [
                $s->employee->employee_code, $s->employee->name,
                $s->employee->department?->name, $s->employee->designation?->name,
                (float) $s->paid_days, (float) $s->lop_days, (float) $s->gross,
                $ded('pf'), $ded('esi'), $ded('pt'), (float) $s->tds_192, $other,
                (float) $s->net_pay, (float) $s->employer_pf, (float) $s->employer_esi, (float) $s->employer_cost,
            ];
        });
    }
}

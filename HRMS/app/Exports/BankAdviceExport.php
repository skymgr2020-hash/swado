<?php

namespace App\Exports;

use App\Exports\Concerns\WithStatutoryStyling;
use App\Models\HrSetting;
use App\Models\PayrollRun;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Salary disbursement advice — one row per employee, net pay to bank account.
 * The format matches what most Indian banks accept for bulk NEFT upload.
 */
class BankAdviceExport implements FromCollection, WithHeadings, WithTitle, WithEvents
{
    use WithStatutoryStyling;

    public function __construct(private readonly PayrollRun $run)
    {
    }

    public function title(): string
    {
        return 'Bank Advice ' . $this->run->period;
    }

    protected function statutoryTitle(): string
    {
        return 'SALARY DISBURSEMENT ADVICE  ·  ' . $this->run->period;
    }

    protected function statutoryNote(): ?string
    {
        $total = number_format((float) ($this->run->totals['net'] ?? 0), 2);

        return 'Pay date: ' . optional($this->run->pay_date)->format('d M Y') . '  ·  Total net: ' . HrSetting::get('currency_symbol', 'INR') . ' ' . $total;
    }

    public function headings(): array
    {
        return ['S.No', 'Emp Code', 'Beneficiary Name', 'Account No.', 'IFSC', 'Amount (INR)', 'Narration'];
    }

    public function collection(): Collection
    {
        return $this->run->payslips()->with('employee')->get()->values()->map(function ($s, $i) {
            $e = $s->employee;

            return [
                $i + 1,
                $e->employee_code,
                $e->bank_account_name ?: $e->name,
                $e->bank_account_no,
                $e->bank_ifsc,
                (float) $s->net_pay,
                'SAL ' . $this->run->period . ' ' . $e->employee_code,
            ];
        });
    }
}

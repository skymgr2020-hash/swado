<?php

namespace App\Support;

use App\Models\HrSetting;
use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfInstance;

class PayslipDocument
{
    public static function data(Payslip $payslip): array
    {
        $payslip->loadMissing('employee.department', 'employee.designation', 'run');

        return [
            'payslip' => $payslip,
            'employee' => $payslip->employee,
            'run' => $payslip->run,
            'employer' => [
                'name' => HrSetting::get('employer_legal_name', config('app.name')),
                'pan' => HrSetting::get('employer_pan'),
                'tan' => HrSetting::get('employer_tan'),
            ],
            'symbol' => HrSetting::get('currency_symbol', '₹'),
        ];
    }

    public static function pdf(Payslip $payslip): PdfInstance
    {
        return Pdf::loadView('documents.payslip', self::data($payslip))->setPaper('a4');
    }
}

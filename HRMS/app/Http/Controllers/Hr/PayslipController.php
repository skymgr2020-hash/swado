<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use App\Support\PayslipDocument;

class PayslipController extends Controller
{
    public function index()
    {
        return view('hr.payroll.payslips', ['payslips' => Payslip::with('employee', 'run')->latest()->paginate(40)]);
    }

    public function pdf(Payslip $payslip)
    {
        return PayslipDocument::pdf($payslip)->download('payslip-' . $payslip->run->period . '-' . $payslip->employee->employee_code . '.pdf');
    }
}

<?php

namespace App\Http\Controllers\Hr;

use App\Exports\BankAdviceExport;
use App\Exports\PayrollRegisterExport;
use App\Http\Controllers\Controller;
use App\Models\Payslip;
use App\Models\PayrollRun;
use App\Services\Hr\HrConfig;
use App\Services\Hr\PayrollService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PayrollRunController extends Controller
{
    public function __construct(private readonly PayrollService $payroll)
    {
    }

    public function index()
    {
        return view('hr.payroll.index', ['runs' => PayrollRun::withCount('payslips')->orderByDesc('period')->paginate(24)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['period' => 'required|date_format:Y-m']);
        $run = $this->payroll->openRun($data['period']);

        return redirect()->route('hr.payroll.show', $run)->with('success', 'Payroll run opened for ' . $data['period'] . '.');
    }

    public function show(PayrollRun $run)
    {
        $run->load(['payslips.employee']);
        $cfg = new HrConfig();

        return view('hr.payroll.show', [
            'run' => $run,
            'symbol' => $cfg->currency(),
            'decimals' => 2,
        ]);
    }

    public function updatePayslip(Request $request, PayrollRun $run, Payslip $payslip)
    {
        abort_unless($payslip->payroll_run_id === $run->id, 404);
        abort_unless($run->isEditable(), 422, 'Run is locked.');

        $data = $request->validate([
            'lop_days' => 'nullable|numeric|min:0',
            'tds_192' => 'nullable|numeric|min:0',
        ]);
        $payslip->fill(array_filter($data, fn ($v) => $v !== null))->save();
        $this->payroll->refreshTotals($run);

        return back()->with('success', 'Payslip updated.');
    }

    public function recalculate(PayrollRun $run)
    {
        $this->payroll->recalculate($run);

        return back()->with('success', 'Recalculated from current salary structures & leave.');
    }

    public function approve(PayrollRun $run)
    {
        $this->payroll->approve($run);

        return back()->with('success', 'Run approved. Journal queued to the Accounts app.');
    }

    public function markPaid(Request $request, PayrollRun $run)
    {
        $this->payroll->markPaid($run, $request->input('pay_date'));

        return back()->with('success', 'Run marked paid. Payment journal queued to Accounts.');
    }

    public function cancel(PayrollRun $run)
    {
        $this->payroll->cancel($run);

        return redirect()->route('hr.payroll.index')->with('success', 'Run cancelled.');
    }

    public function register(PayrollRun $run)
    {
        return Excel::download(new PayrollRegisterExport($run), "payroll-register-{$run->period}.xlsx");
    }

    public function bankAdvice(PayrollRun $run)
    {
        return Excel::download(new BankAdviceExport($run), "bank-advice-{$run->period}.xlsx");
    }
}

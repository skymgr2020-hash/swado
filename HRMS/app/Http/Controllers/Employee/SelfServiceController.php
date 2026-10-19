<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payslip;
use App\Services\Hr\HrConfig;
use App\Services\Hr\LeaveService;
use App\Support\Form16Document;
use App\Support\PayslipDocument;
use Illuminate\Http\Request;

class SelfServiceController extends Controller
{
    public function __construct(private readonly LeaveService $leave)
    {
    }

    private function employee(): Employee
    {
        return Employee::where('user_id', auth()->id())->firstOr(fn () => abort(404, 'No employee profile is linked to your account.'));
    }

    public function dashboard()
    {
        $emp = $this->employee();
        $cfg = new HrConfig();

        return view('employee.dashboard', [
            'employee' => $emp->load('department', 'designation'),
            'latest_payslip' => $emp->payslips()->with('run')->latest()->first(),
            'balances' => $this->leave->balanceFor($emp),
            'pending_leave' => $emp->leaveRequests()->where('status', 'pending')->count(),
            'symbol' => $cfg->currency(),
            'decimals' => 2,
        ]);
    }

    public function payslips()
    {
        $emp = $this->employee();

        return view('employee.payslips', [
            'employee' => $emp,
            'payslips' => $emp->payslips()->with('run')
                ->whereHas('run', fn ($q) => $q->whereIn('status', ['approved', 'paid', 'posted']))
                ->latest()->paginate(24),
            'symbol' => (new HrConfig())->currency(),
            'decimals' => 2,
        ]);
    }

    public function payslipPdf(Payslip $payslip)
    {
        abort_unless($payslip->employee_id === $this->employee()->id, 403);
        abort_unless(in_array($payslip->run->status, ['approved', 'paid', 'posted'], true), 404);

        return PayslipDocument::pdf($payslip)->download('payslip-' . $payslip->run->period . '.pdf');
    }

    public function form16()
    {
        $emp = $this->employee();
        $cfg = new HrConfig();
        $years = $emp->payslips()->with('run')->get()
            ->pluck('run.period')->filter()
            ->map(fn ($p) => $cfg->fy($p . '-01'))->unique()->values();

        return view('employee.form16', ['employee' => $emp, 'years' => $years]);
    }

    public function form16Pdf(string $fy)
    {
        return Form16Document::pdf($this->employee(), $fy)->download('form16-' . $fy . '.pdf');
    }

    public function leave()
    {
        $emp = $this->employee();

        return view('employee.leave', [
            'employee' => $emp,
            'balances' => $this->leave->balanceFor($emp),
            'types' => LeaveType::where('is_active', true)->orderBy('name')->get(),
            'requests' => $emp->leaveRequests()->with('type')->latest()->limit(50)->get(),
        ]);
    }

    public function applyLeave(Request $request)
    {
        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ]);
        $this->leave->apply($this->employee(), (int) $data['leave_type_id'], $data['start_date'], $data['end_date'], $data['reason'] ?? null);

        return back()->with('success', 'Leave request submitted for approval.');
    }

    public function cancelLeave(LeaveRequest $leaveRequest)
    {
        abort_unless($leaveRequest->employee_id === $this->employee()->id, 403);
        $this->leave->cancel($leaveRequest);

        return back()->with('success', 'Leave request cancelled.');
    }

    public function profile()
    {
        return view('employee.profile', ['employee' => $this->employee()->load('department', 'designation')]);
    }

    public function updateProfile(Request $request)
    {
        $this->employee()->update($request->validate([
            'phone' => 'nullable|string|max:20',
            'personal_email' => 'nullable|email',
            'bank_account_name' => 'nullable|string|max:120',
            'bank_account_no' => 'nullable|string|max:40',
            'bank_ifsc' => 'nullable|string|max:20',
        ]));

        return back()->with('success', 'Profile updated.');
    }
}

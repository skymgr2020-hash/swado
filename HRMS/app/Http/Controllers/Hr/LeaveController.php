<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\Hr\LeaveService;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function __construct(private readonly LeaveService $leave)
    {
    }

    public function index()
    {
        return view('hr.leave.index', [
            'pending' => LeaveRequest::with('employee', 'type')->where('status', 'pending')->latest()->get(),
            'types' => LeaveType::orderBy('name')->get(),
            'balances' => LeaveBalance::with('employee', 'type')->where('year', now()->year)->get()->groupBy('employee_id'),
        ]);
    }

    public function decide(Request $request, LeaveRequest $leaveRequest)
    {
        $data = $request->validate(['decision' => 'required|in:approve,reject', 'note' => 'nullable|string|max:500']);
        $data['decision'] === 'approve'
            ? $this->leave->approve($leaveRequest, $data['note'] ?? null)
            : $this->leave->reject($leaveRequest, $data['note'] ?? null);

        return back()->with('success', 'Leave ' . $data['decision'] . 'd.');
    }

    public function storeType(Request $request)
    {
        LeaveType::create($request->validate([
            'name' => 'required|string|max:80',
            'code' => 'required|string|max:20|unique:leave_types,code',
            'accrual' => 'required|in:annual_fixed,monthly_accrual,unpaid',
            'annual_quota' => 'nullable|numeric|min:0',
            'paid' => 'nullable|boolean',
        ]) + ['paid' => $request->boolean('paid'), 'is_active' => true]);

        return back()->with('success', 'Leave type added.');
    }

    public function updateType(Request $request, LeaveType $leaveType)
    {
        $leaveType->update($request->validate([
            'name' => 'required|string|max:80',
            'accrual' => 'required|in:annual_fixed,monthly_accrual,unpaid',
            'annual_quota' => 'nullable|numeric|min:0',
            'paid' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]) + ['paid' => $request->boolean('paid'), 'is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Leave type updated.');
    }
}

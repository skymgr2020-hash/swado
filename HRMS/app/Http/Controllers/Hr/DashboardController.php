<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Services\Hr\HrConfig;

class DashboardController extends Controller
{
    public function index()
    {
        $lastRun = PayrollRun::latest('period')->first();
        $cfg = new HrConfig();

        return view('hr.dashboard', [
            'headcount' => Employee::active()->count(),
            'total_employees' => Employee::count(),
            'pending_leave' => LeaveRequest::where('status', 'pending')->count(),
            'last_run' => $lastRun,
            'monthly_cost' => (float) ($lastRun->totals['employer_cost'] ?? 0),
            'statutory_due' => [
                'pf' => (float) ($lastRun->totals['pf_ee'] ?? 0) + (float) ($lastRun->totals['pf_er'] ?? 0),
                'esi' => (float) ($lastRun->totals['esi_ee'] ?? 0) + (float) ($lastRun->totals['esi_er'] ?? 0),
                'pt' => (float) ($lastRun->totals['pt'] ?? 0),
                'tds' => (float) ($lastRun->totals['tds_192'] ?? 0),
            ],
            'symbol' => $cfg->currency(),
            'decimals' => 2,
        ]);
    }
}

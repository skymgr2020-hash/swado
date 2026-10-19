<?php

namespace App\Http\Controllers\Hr;

use App\Exports\Form24qExport;
use App\Exports\StatutoryContributionExport;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Services\Hr\HrConfig;
use App\Support\Form16Document;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class HrReportController extends Controller
{
    public function index()
    {
        return view('hr.reports.index', [
            'runs' => PayrollRun::whereIn('status', ['approved', 'paid', 'posted'])->orderByDesc('period')->get(),
            'employees' => Employee::orderBy('first_name')->get(),
            'fy' => (new HrConfig())->fy(now()),
        ]);
    }

    public function statutory(Request $request, string $kind)
    {
        abort_unless(in_array($kind, ['pf', 'esi', 'pt'], true), 404);
        $run = PayrollRun::where('period', $request->input('period', now()->format('Y-m')))->firstOrFail();

        return Excel::download(new StatutoryContributionExport($run, $kind), strtoupper($kind) . "-{$run->period}.xlsx");
    }

    public function form24q(Request $request)
    {
        $fy = $request->input('fy') ?: (new HrConfig())->fy(now());

        return Excel::download(new Form24qExport($fy), "form24q-{$fy}.xlsx");
    }

    public function form16(Request $request, Employee $employee)
    {
        $fy = $request->input('fy') ?: (new HrConfig())->fy(now());

        return Form16Document::pdf($employee, $fy)->download('form16-' . $employee->employee_code . '-' . $fy . '.pdf');
    }
}

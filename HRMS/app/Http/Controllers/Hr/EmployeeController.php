<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\HrDepartment;
use App\Models\HrDesignation;
use App\Models\SalaryStructure;
use App\Models\User;
use App\Support\WebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::with('department', 'designation', 'user')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('first_name', 'like', "%{$request->q}%")
                ->orWhere('last_name', 'like', "%{$request->q}%")
                ->orWhere('employee_code', 'like', "%{$request->q}%")))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('first_name')
            ->paginate(25)->withQueryString();

        return view('hr.employees.index', compact('employees'));
    }

    public function create()
    {
        return view('hr.employees.form', [
            'employee' => new Employee(['date_of_joining' => now()->toDateString(), 'employment_type' => 'full_time', 'tax_regime' => 'new']),
            'departments' => HrDepartment::where('is_active', true)->orderBy('name')->get(),
            'designations' => HrDesignation::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if (! empty($data['pan']) && ! preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', strtoupper($data['pan']))) {
            return back()->withInput()->withErrors(['pan' => 'Enter a valid PAN.']);
        }

        $employee = DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name' => trim($data['first_name'] . ' ' . ($data['last_name'] ?? '')),
                'email' => $data['work_email'],
                'phone' => $data['phone'] ?? ('E' . random_int(1000000000, 9999999999)),
                'password' => Hash::make(Str::random(32)),
                'is_active' => true,
            ]);
            $user->assignRole('employee');

            $employee = Employee::create(collect($data)->except('work_email')->all() + ['user_id' => $user->id]);
            $this->syncStructure($employee, $request);
            $this->notifyIdp($user);

            return $employee;
        });

        return redirect()->route('hr.employees.show', $employee)->with('success', 'Employee added and login provisioned.');
    }

    public function show(Employee $employee)
    {
        $employee->load('department', 'designation', 'user', 'structures', 'payslips.run');

        return view('hr.employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        return view('hr.employees.form', [
            'employee' => $employee->load('currentStructure'),
            'departments' => HrDepartment::where('is_active', true)->orderBy('name')->get(),
            'designations' => HrDesignation::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $this->validated($request, $employee);
        $employee->update(collect($data)->except('work_email')->all());
        $employee->user?->update(['name' => $employee->name, 'email' => $data['work_email']]);
        $this->syncStructure($employee, $request);
        if ($employee->user) {
            $this->notifyIdp($employee->user);
        }

        return redirect()->route('hr.employees.show', $employee)->with('success', 'Employee updated.');
    }

    public function deactivate(Request $request, Employee $employee)
    {
        $data = $request->validate(['status' => 'required|in:resigned,terminated,on_notice', 'date_of_exit' => 'nullable|date']);
        $employee->update($data + ['date_of_exit' => $data['date_of_exit'] ?? now()->toDateString()]);
        if ($data['status'] !== 'on_notice') {
            $employee->user?->update(['is_active' => false]);
        }

        return back()->with('success', 'Employee marked ' . $data['status'] . '.');
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        return $request->validate([
            'employee_code' => 'required|string|max:40|unique:employees,employee_code,' . ($employee->id ?? 'NULL'),
            'first_name' => 'required|string|max:120',
            'last_name' => 'nullable|string|max:120',
            'work_email' => 'required|email|unique:users,email,' . ($employee->user_id ?? 'NULL'),
            'personal_email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'department_id' => 'nullable|exists:hr_departments,id',
            'designation_id' => 'nullable|exists:hr_designations,id',
            'date_of_joining' => 'required|date',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'status' => 'nullable|in:active,on_notice,resigned,terminated,on_leave',
            'pan' => 'nullable|string|max:15',
            'aadhaar_last4' => 'nullable|string|max:4',
            'pf_uan' => 'nullable|string|max:20',
            'esi_ic_number' => 'nullable|string|max:20',
            'bank_account_name' => 'nullable|string|max:120',
            'bank_account_no' => 'nullable|string|max:40',
            'bank_ifsc' => 'nullable|string|max:20',
            'tax_regime' => 'required|in:old,new',
            'ctc_annual' => 'nullable|numeric|min:0',
            'structure_effective_from' => 'nullable|date',
            'earn_code' => 'nullable|array',
            'earn_label' => 'nullable|array',
            'earn_calc' => 'nullable|array',
            'earn_value' => 'nullable|array',
        ]);
    }

    private function syncStructure(Employee $employee, Request $request): void
    {
        $codes = $request->input('earn_code', []);
        if (empty(array_filter($codes))) {
            return;
        }
        $earnings = [];
        foreach ($codes as $i => $code) {
            if (! $code) {
                continue;
            }
            $earnings[] = [
                'code' => Str::slug($code, '_'),
                'label' => $request->input("earn_label.$i") ?: ucfirst($code),
                'calc' => $request->input("earn_calc.$i") === 'pct_basic' ? 'pct_basic' : 'flat',
                'value' => (float) $request->input("earn_value.$i", 0),
            ];
        }
        $employee->structures()->update(['is_current' => false]);
        SalaryStructure::create([
            'employee_id' => $employee->id,
            'effective_from' => $request->input('structure_effective_from') ?: now()->toDateString(),
            'earnings' => $earnings,
            'deductions' => [],
            'ctc_annual' => (float) $request->input('ctc_annual', 0),
            'is_current' => true,
            'created_by' => auth()->id(),
        ]);
    }

    /** Tell the admin identity provider about the new/updated employee login. */
    private function notifyIdp(User $user): void
    {
        $base = rtrim((string) env('ADMIN_BASE_URL', ''), '/');
        $secret = (string) env('INTEGRATION_ADMIN_SECRET', '');
        if ($base === '' || $secret === '') {
            return;
        }
        try {
            $body = json_encode(['data' => [
                'name' => $user->name, 'email' => $user->email, 'phone' => $user->phone,
                'password' => $user->getAuthPassword(),
            ]], JSON_UNESCAPED_SLASHES);
            Http::withHeaders(WebhookSignature::headers($body, $secret))
                ->withBody($body, 'application/json')->timeout(10)
                ->post($base . '/api/ingest/employee-user');
        } catch (\Throwable $e) {
            // best-effort; admin can also be backfilled
        }
    }
}

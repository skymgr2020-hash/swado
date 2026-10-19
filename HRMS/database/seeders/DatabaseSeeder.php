<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\HrDepartment;
use App\Models\HrDesignation;
use App\Models\SalaryStructure;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['super_admin', 'admin', 'hr_manager', 'employee'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        // Local HR account so the app is usable before the identity sync runs.
        User::updateOrCreate(
            ['email' => 'hr@swado.local'],
            ['name' => 'HR Manager', 'phone' => '9000000020', 'password' => Hash::make('password'), 'is_active' => true]
        )->syncRoles(['hr_manager']);

        // A sample department, designation and employee (with a login) for a smoke test.
        $dept = HrDepartment::firstOrCreate(['name' => 'Operations'], ['code' => 'OPS', 'is_active' => true]);
        $desig = HrDesignation::firstOrCreate(['name' => 'Delivery Executive'], ['level' => 'L1', 'is_active' => true]);

        $empUser = User::updateOrCreate(
            ['email' => 'employee@swado.local'],
            ['name' => 'Sample Employee', 'phone' => '9000000021', 'password' => Hash::make('password'), 'is_active' => true]
        );
        $empUser->syncRoles(['employee']);

        $employee = Employee::firstOrCreate(
            ['employee_code' => 'EMP0001'],
            [
                'user_id' => $empUser->id,
                'first_name' => 'Sample', 'last_name' => 'Employee',
                'personal_email' => 'sample.employee@example.com',
                'department_id' => $dept->id, 'designation_id' => $desig->id,
                'date_of_joining' => now()->subYear()->toDateString(),
                'employment_type' => 'full_time', 'status' => 'active',
                'pan' => 'ABCPE1234F', 'pf_uan' => '100200300400',
                'bank_account_name' => 'Sample Employee', 'bank_account_no' => '000111222333', 'bank_ifsc' => 'HDFC0000001',
                'tax_regime' => 'new', 'ctc_annual' => 480000,
            ]
        );

        if ($employee->wasRecentlyCreated) {
            SalaryStructure::create([
                'employee_id' => $employee->id,
                'effective_from' => now()->subYear()->toDateString(),
                'earnings' => [
                    ['code' => 'basic', 'label' => 'Basic', 'calc' => 'flat', 'value' => 20000],
                    ['code' => 'hra', 'label' => 'HRA', 'calc' => 'pct_basic', 'value' => 40],
                    ['code' => 'special', 'label' => 'Special Allowance', 'calc' => 'flat', 'value' => 6000],
                ],
                'deductions' => [],
                'ctc_annual' => 480000,
                'is_current' => true,
            ]);
        }

        $this->command?->info('HRMS seeded. Logins: hr@swado.local / password  ·  employee@swado.local / password');
    }
}

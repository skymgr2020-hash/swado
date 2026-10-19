<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processed_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->string('topic', 60);
            $table->timestamp('processed_at');
            $table->timestamps();
        });

        Schema::create('hr_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('hr_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->nullable();
            $table->unsignedBigInteger('head_employee_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hr_designations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('level', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 40)->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->date('dob')->nullable();
            $table->string('gender', 12)->nullable();
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('hr_designations')->nullOnDelete();
            $table->date('date_of_joining');
            $table->date('date_of_exit')->nullable();
            $table->string('employment_type', 20)->default('full_time');
            $table->string('status', 20)->default('active');
            $table->string('pan', 15)->nullable();
            $table->string('aadhaar_last4', 4)->nullable();
            $table->string('pf_uan', 20)->nullable();
            $table->string('esi_ic_number', 20)->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_no', 40)->nullable();
            $table->string('bank_ifsc', 20)->nullable();
            $table->string('tax_regime', 4)->default('new');
            $table->decimal('ctc_annual', 14, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('effective_from');
            $table->json('earnings');
            $table->json('deductions')->nullable();
            $table->decimal('ctc_annual', 14, 2)->default(0);
            $table->boolean('is_current')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7)->unique();
            $table->date('pay_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('totals')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('accrual_journal_ref')->nullable();   // entry_no returned by Accounts
            $table->string('payment_journal_ref')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedSmallInteger('working_days')->default(30);
            $table->decimal('paid_days', 5, 2)->default(30);
            $table->decimal('lop_days', 5, 2)->default(0);
            $table->json('earnings');
            $table->json('deductions');
            $table->decimal('gross', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('net_pay', 14, 2)->default(0);
            $table->decimal('employer_pf', 14, 2)->default(0);
            $table->decimal('employer_esi', 14, 2)->default(0);
            $table->decimal('employer_cost', 14, 2)->default(0);
            $table->decimal('tds_192', 14, 2)->default(0);
            $table->json('snapshot')->nullable();
            $table->timestamps();
            $table->unique(['payroll_run_id', 'employee_id']);
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->string('accrual', 20)->default('annual_fixed');
            $table->decimal('annual_quota', 6, 2)->default(0);
            $table->boolean('paid')->default(true);
            $table->decimal('carry_forward_cap', 6, 2)->nullable();
            $table->json('applies_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('opening', 6, 2)->default(0);
            $table->decimal('accrued', 6, 2)->default(0);
            $table->decimal('used', 6, 2)->default(0);
            $table->decimal('pending', 6, 2)->default(0);
            $table->timestamps();
            $table->unique(['employee_id', 'leave_type_id', 'year']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days', 5, 2);
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note')->nullable();
            $table->timestamps();
        });

        $now = now();
        foreach ([
            ['Casual Leave', 'CL', 'annual_fixed', 12, true],
            ['Sick Leave', 'SL', 'annual_fixed', 6, true],
            ['Earned Leave', 'EL', 'monthly_accrual', 15, true],
            ['Loss of Pay', 'LOP', 'unpaid', 0, false],
        ] as [$name, $code, $accrual, $quota, $paid]) {
            DB::table('leave_types')->updateOrInsert(['code' => $code], [
                'name' => $name, 'accrual' => $accrual, 'annual_quota' => $quota,
                'paid' => $paid, 'is_active' => true, 'updated_at' => $now, 'created_at' => $now,
            ]);
        }

        foreach ([
            'pay_frequency' => 'monthly', 'pay_day' => '1', 'payment_account' => '1000',
            'default_tax_regime' => 'new',
            'pf_employee_rate' => '12', 'pf_employer_rate' => '12', 'pf_wage_ceiling' => '15000', 'eps_rate' => '8.33',
            'esi_employee_rate' => '0.75', 'esi_employer_rate' => '3.25', 'esi_wage_ceiling' => '21000',
            'pt_mode' => 'flat', 'pt_flat_amount' => '200',
            'tds192_mode' => 'manual', 'tds192_flat_rate' => '0',
            'currency_symbol' => '₹', 'currency_decimals' => '2',
            'employer_legal_name' => 'Swado Technologies Private Limited', 'employer_tan' => 'MUMF12345K',
            'employer_pan' => 'AABCF1234K',
        ] as $k => $v) {
            DB::table('hr_settings')->updateOrInsert(['key' => $k], ['value' => $v, 'updated_at' => $now, 'created_at' => $now]);
        }
    }

    public function down(): void
    {
        foreach (['leave_requests', 'leave_balances', 'leave_types', 'payslips', 'payroll_runs',
            'salary_structures', 'employees', 'hr_designations', 'hr_departments', 'hr_settings', 'processed_events'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

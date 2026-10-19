<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chart-of-accounts rows the HRMS payroll journals post into
 * (accrual: Dr 5100/5110, Cr 2015/2370/2380/2390 ; payment: Dr 2015, Cr 1000).
 */
return new class extends Migration
{
    private array $accounts = [
        ['5100', 'Salaries & wages', 'expense', 'operating_expense'],
        ['5110', 'Employer statutory contributions (PF/ESI)', 'expense', 'operating_expense'],
        ['2015', 'Net salary payable', 'liability', 'current_liability'],
        ['2370', 'TDS payable — Sec 192 (salary)', 'liability', 'tax_liability'],
        ['2380', 'Provident Fund payable', 'liability', 'current_liability'],
        ['2390', 'ESI & Professional Tax payable', 'liability', 'current_liability'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('chart_of_accounts')) {
            return;
        }
        foreach ($this->accounts as [$code, $name, $type, $subtype]) {
            DB::table('chart_of_accounts')->updateOrInsert(
                ['code' => $code],
                ['name' => $name, 'type' => $type, 'subtype' => $subtype, 'is_system' => true, 'is_active' => true, 'updated_at' => now(), 'created_at' => now()],
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chart_of_accounts')) {
            DB::table('chart_of_accounts')->whereIn('code', array_column($this->accounts, 0))->delete();
        }
    }
};

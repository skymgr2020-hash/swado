<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    protected $fillable = [
        'payroll_run_id', 'employee_id', 'working_days', 'paid_days', 'lop_days',
        'earnings', 'deductions', 'gross', 'total_deductions', 'net_pay',
        'employer_pf', 'employer_esi', 'employer_cost', 'tds_192', 'snapshot',
    ];

    protected $casts = [
        'earnings' => 'array',
        'deductions' => 'array',
        'snapshot' => 'array',
        'paid_days' => 'decimal:2',
        'lop_days' => 'decimal:2',
        'gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'employer_pf' => 'decimal:2',
        'employer_esi' => 'decimal:2',
        'employer_cost' => 'decimal:2',
        'tds_192' => 'decimal:2',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

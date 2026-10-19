<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    protected $fillable = [
        'employee_code', 'user_id', 'first_name', 'last_name', 'personal_email', 'phone',
        'dob', 'gender', 'department_id', 'designation_id', 'date_of_joining', 'date_of_exit',
        'employment_type', 'status', 'pan', 'aadhaar_last4', 'pf_uan', 'esi_ic_number',
        'bank_account_name', 'bank_account_no', 'bank_ifsc', 'tax_regime', 'ctc_annual', 'meta',
    ];

    protected $casts = [
        'dob' => 'date', 'date_of_joining' => 'date', 'date_of_exit' => 'date',
        'ctc_annual' => 'decimal:2', 'meta' => 'array',
    ];

    public function getNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(HrDepartment::class, 'department_id');
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(HrDesignation::class, 'designation_id');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function structures(): HasMany
    {
        return $this->hasMany(SalaryStructure::class);
    }

    public function currentStructure(): HasOne
    {
        return $this->hasOne(SalaryStructure::class)->where('is_current', true)->latestOfMany('effective_from');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }
}

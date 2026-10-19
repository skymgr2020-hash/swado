<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = ['name', 'code', 'accrual', 'annual_quota', 'paid', 'carry_forward_cap', 'applies_to', 'is_active'];

    protected $casts = [
        'annual_quota' => 'decimal:2',
        'carry_forward_cap' => 'decimal:2',
        'paid' => 'boolean',
        'applies_to' => 'array',
        'is_active' => 'boolean',
    ];
}

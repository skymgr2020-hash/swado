<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $fillable = ['employee_id', 'leave_type_id', 'year', 'opening', 'accrued', 'used', 'pending'];

    protected $casts = [
        'opening' => 'decimal:2', 'accrued' => 'decimal:2', 'used' => 'decimal:2', 'pending' => 'decimal:2',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getAvailableAttribute(): float
    {
        return round((float) $this->opening + (float) $this->accrued - (float) $this->used - (float) $this->pending, 2);
    }
}

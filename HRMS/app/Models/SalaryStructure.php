<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructure extends Model
{
    protected $fillable = ['employee_id', 'effective_from', 'earnings', 'deductions', 'ctc_annual', 'is_current', 'created_by'];

    protected $casts = [
        'effective_from' => 'date',
        'earnings' => 'array',
        'deductions' => 'array',
        'ctc_annual' => 'decimal:2',
        'is_current' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

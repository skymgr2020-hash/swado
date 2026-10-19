<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $fillable = [
        'period', 'pay_date', 'status', 'totals', 'approved_by', 'approved_at',
        'paid_by', 'paid_at', 'accrual_journal_ref', 'payment_journal_ref', 'notes',
    ];

    protected $casts = [
        'pay_date' => 'date',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'totals' => 'array',
    ];

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'processing'], true);
    }
}

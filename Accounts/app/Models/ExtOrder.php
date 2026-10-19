<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Mirror of admin/'s orders — only the columns the GST read models need. */
class ExtOrder extends Model
{
    protected $table = 'ext_orders';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'id', 'order_number', 'status', 'invoice_type', 'invoice_number', 'invoice_date',
        'place_of_supply', 'supplier_gstin', 'subtotal', 'delivery_fee', 'platform_fee',
        'total', 'restaurant_id', 'branch_id', 'tax_breakdown', 'created_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'tax_breakdown' => 'array',
        'created_at' => 'datetime',
    ];
}

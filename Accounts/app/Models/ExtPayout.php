<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Mirror of admin/'s payouts — for the settlement statements. */
class ExtPayout extends Model
{
    protected $table = 'ext_payouts';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'id', 'uuid', 'restaurant_id', 'driver_id', 'restaurant_name', 'driver_name',
        'gross_amount', 'platform_commission', 'gst_on_commission', 'payment_gateway_fee',
        'pre_tax_amount', 'tds_amount', 'tds_section', 'tcs_amount', 'net_amount', 'status', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];
}

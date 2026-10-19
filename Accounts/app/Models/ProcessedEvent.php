<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessedEvent extends Model
{
    protected $fillable = ['event_id', 'topic', 'processed_at'];
    protected $casts = ['processed_at' => 'datetime'];
}

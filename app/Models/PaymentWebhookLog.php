<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhookLog extends Model
{
     protected $fillable = [
        'idempotency_key',
        'payload',
        'processed_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
    
}

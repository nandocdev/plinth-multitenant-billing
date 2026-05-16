<?php

namespace Nandocdev\Dlocal\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'dlocal_transactions';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'dlocal_id',
        'amount',
        'currency',
        'country',
        'status',
        'payment_method_id',
        'last_webhook_payload'
    ];

    protected $casts = [
        'last_webhook_payload' => 'array',
        'amount' => 'decimal:2',
        'status' => \Nandocdev\Dlocal\Core\Enums\TransactionStatus::class,
    ];
}

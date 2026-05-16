<?php

namespace Plinth\MultiTenantBilling\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'provider_id',
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
        'status' => \Plinth\MultiTenantBilling\Core\Enums\TransactionStatus::class,
    ];
}

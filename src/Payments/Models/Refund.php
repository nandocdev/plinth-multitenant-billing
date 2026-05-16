<?php

namespace Plinth\MultiTenantBilling\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $table = 'refunds';
    protected $fillable = [
        'tenant_id', 'transaction_id', 'provider_refund_id', 
        'amount', 'currency', 'status', 'reason'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}

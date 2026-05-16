<?php

namespace Plinth\MultiTenantBilling\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    protected $table = 'disputes';
    protected $fillable = [
        'tenant_id', 'transaction_id', 'provider_dispute_id', 
        'amount', 'currency', 'status', 'reason'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}

<?php

namespace Plinth\MultiTenantBilling\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoices';
    protected $fillable = ['tenant_id', 'subscription_id', 'provider_invoice_id', 'amount', 'currency', 'status', 'issued_at'];
    
    protected $casts = [
        'issued_at' => 'datetime',
        'amount' => 'decimal:2',
    ];
}

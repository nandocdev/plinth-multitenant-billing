<?php

namespace Plinth\MultiTenantBilling\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';
    protected $fillable = [
        'tenant_id', 'customer_id', 'type', 'token', 
        'last4', 'exp_month', 'exp_year', 'is_default'
    ];
    
    protected $casts = [
        'is_default' => 'boolean',
    ];
}

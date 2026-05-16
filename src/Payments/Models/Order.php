<?php

namespace Plinth\MultiTenantBilling\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    protected $fillable = ['tenant_id', 'customer_id', 'amount', 'currency', 'description', 'status'];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'status' => \Plinth\MultiTenantBilling\Core\Enums\TransactionStatus::class,
    ];
}

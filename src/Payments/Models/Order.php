<?php

namespace Nandocdev\Dlocal\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'dlocal_orders';
    protected $fillable = ['tenant_id', 'customer_id', 'amount', 'currency', 'description', 'status'];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'status' => \Nandocdev\Dlocal\Core\Enums\TransactionStatus::class,
    ];
}

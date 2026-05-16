<?php

namespace Nandocdev\Dlocal\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $table = 'dlocal_refunds';
    protected $fillable = [
        'tenant_id', 'transaction_id', 'dlocal_refund_id', 
        'amount', 'currency', 'status', 'reason'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}

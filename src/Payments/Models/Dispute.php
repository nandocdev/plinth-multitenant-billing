<?php

namespace Nandocdev\Dlocal\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    protected $table = 'dlocal_disputes';
    protected $fillable = [
        'tenant_id', 'transaction_id', 'dlocal_dispute_id', 
        'amount', 'currency', 'status', 'reason'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}

<?php

namespace Nandocdev\Dlocal\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionItem extends Model
{
    protected $table = 'dlocal_subscription_items';
    protected $fillable = ['subscription_id', 'name', 'amount', 'quantity'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}

<?php

namespace Plinth\MultiTenantBilling\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionItem extends Model
{
    protected $table = 'subscription_items';
    protected $fillable = ['subscription_id', 'name', 'amount', 'quantity'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}

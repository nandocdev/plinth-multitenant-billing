<?php

namespace Plinth\MultiTenantBilling\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $table = 'subscriptions';
    protected $fillable = ['tenant_id', 'plan_id', 'provider_subscription_id', 'status', 'current_period_end'];
    
    protected $casts = [
        'current_period_end' => 'datetime',
    ];
}

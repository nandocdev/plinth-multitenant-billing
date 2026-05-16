<?php

namespace Nandocdev\Dlocal\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $table = 'dlocal_subscriptions';
    protected $fillable = ['tenant_id', 'plan_id', 'dlocal_subscription_id', 'status', 'current_period_end'];
    
    protected $casts = [
        'current_period_end' => 'datetime',
    ];
}

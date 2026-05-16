<?php

namespace Plinth\MultiTenantBilling\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'plans';
    protected $fillable = ['provider_plan_id', 'name', 'currency', 'amount', 'interval', 'interval_count'];
}

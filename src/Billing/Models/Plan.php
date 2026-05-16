<?php

namespace Nandocdev\Dlocal\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'dlocal_plans';
    protected $fillable = ['dlocal_plan_id', 'name', 'currency', 'amount', 'interval', 'interval_count'];
}

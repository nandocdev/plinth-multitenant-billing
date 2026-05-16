<?php

namespace Plinth\MultiTenantBilling\Billing\Usage;

use Illuminate\Database\Eloquent\Model;

class UsageSnapshot extends Model
{
    protected $fillable = [
        'tenant_id',
        'feature',
        'total_usage',
        'snapshot_at',
    ];

    protected $casts = [
        'snapshot_at' => 'datetime',
        'total_usage' => 'integer',
    ];
}

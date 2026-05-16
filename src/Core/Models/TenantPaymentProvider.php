<?php

namespace Plinth\MultiTenantBilling\Core\Models;

use Illuminate\Database\Eloquent\Model;

class TenantPaymentProvider extends Model
{
    protected $fillable = [
        'tenant_id',
        'provider',
        'credentials',
        'status',
    ];

    protected $casts = [
        'credentials' => 'array',
    ];
}

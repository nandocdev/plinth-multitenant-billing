<?php

namespace Plinth\MultiTenantBilling\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class BillingAttempt extends Model
{
    protected $table = 'billing_attempts';
    protected $fillable = ['invoice_id', 'status', 'error_message', 'attempted_at'];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];
}

<?php

namespace Nandocdev\Dlocal\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class BillingAttempt extends Model
{
    protected $table = 'dlocal_billing_attempts';
    protected $fillable = ['invoice_id', 'status', 'error_message', 'attempted_at'];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];
}

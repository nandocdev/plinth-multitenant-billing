<?php

namespace Nandocdev\Dlocal\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'dlocal_invoices';
    protected $fillable = ['tenant_id', 'subscription_id', 'dlocal_invoice_id', 'amount', 'currency', 'status', 'issued_at'];
    
    protected $casts = [
        'issued_at' => 'datetime',
        'amount' => 'decimal:2',
    ];
}

<?php

namespace Plinth\MultiTenantBilling\Core\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookCall extends Model
{
    protected $table = 'webhook_calls';

    protected $fillable = [
        'payload',
        'event_type',
        'status',
        'error_message',
        'processed_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}

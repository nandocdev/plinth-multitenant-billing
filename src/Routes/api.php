<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plinth\MultiTenantBilling\Core\Http\Controllers\WebhookController;

Route::post('/api/{provider}/webhooks/{tenant_id}', [WebhookController::class, 'handle'])
    ->name('billing.webhooks.handle')
    ->whereIn('provider', ['dlocal', 'stripe', 'paguelofacil'])
    ->middleware('api');

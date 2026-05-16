<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Nandocdev\Dlocal\Core\Http\Controllers\WebhookController;

Route::post('/api/dlocal/webhooks', [WebhookController::class, 'handle'])
    ->name('dlocal.webhooks.handle')
    ->middleware('api');

<?php

namespace Plinth\MultiTenantBilling\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Plinth\MultiTenantBilling\Core\Models\WebhookCall;
use Plinth\MultiTenantBilling\Core\Jobs\ProcessWebhookJob;
use Plinth\MultiTenantBilling\Contracts\PaymentProvider;

class WebhookController extends Controller
{
    public function __construct(protected PaymentProvider $provider) {}

    public function handle(Request $request)
    {
        $payload = $request->all();

        if (!$this->provider->verifyWebhook($request)) {
            Log::warning('Webhook: Invalid signature', ['payload' => $payload]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Store raw payload
        $webhookCall = WebhookCall::create([
            'payload' => $payload,
            'event_type' => $payload['status'] ?? 'UNKNOWN',
            'status' => 'PENDING',
        ]);

        // Dispatch async job
        ProcessWebhookJob::dispatch($webhookCall);

        Log::info('Webhook Queued', ['webhook_call_id' => $webhookCall->id]);

        return response()->json(['message' => 'OK'], 200);
    }
}

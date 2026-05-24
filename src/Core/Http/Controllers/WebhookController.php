<?php

namespace Plinth\MultiTenantBilling\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Plinth\MultiTenantBilling\Core\Models\WebhookCall;
use Plinth\MultiTenantBilling\Core\Jobs\ProcessWebhookJob;
use Plinth\MultiTenantBilling\Core\Factories\PaymentProviderFactory;

class WebhookController extends Controller
{
    public function __construct(protected PaymentProviderFactory $factory) {}

    public function handle(Request $request, string $provider, $tenant_id)
    {
        $payload = $request->all();

        try {
            $gateway = $this->factory->makePaymentProvider($tenant_id);

            if (!$gateway->verifyWebhook($request)) {
                Log::warning("Webhook: Invalid signature for {$provider}", [
                    'tenant_id' => $tenant_id,
                    'payload' => $payload
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        } catch (\Exception $e) {
            Log::error("Webhook error: " . $e->getMessage(), [
                'tenant_id' => $tenant_id,
                'provider' => $provider
            ]);
            return response()->json(['error' => 'Provider or tenant not found'], 404);
        }

        // Store raw payload
        $webhookCall = WebhookCall::create([
            'payload' => $payload,
            'event_type' => $this->getEventType($provider, $payload),
            'status' => 'PENDING',
        ]);

        // Dispatch async job
        ProcessWebhookJob::dispatch($webhookCall);

        Log::info("Webhook Queued: {$provider}", [
            'webhook_call_id' => $webhookCall->id,
            'tenant_id' => $tenant_id
        ]);

        return response()->json(['message' => 'OK'], 200);
    }

    protected function getEventType(string $provider, array $payload): string
    {
        if ($provider === 'stripe') {
            return $payload['type'] ?? 'UNKNOWN';
        }

        return $payload['status'] ?? 'UNKNOWN';
    }
}

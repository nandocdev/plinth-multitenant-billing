<?php

namespace Nandocdev\Dlocal\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Nandocdev\Dlocal\Core\Models\WebhookCall;
use Nandocdev\Dlocal\Core\Jobs\ProcessWebhookJob;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('Authorization');

        if (!$this->verifySignature($request->getContent(), $signature)) {
            Log::warning('dLocal Webhook: Invalid signature', ['payload' => $payload]);
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

        Log::info('dLocal Webhook Queued', ['webhook_call_id' => $webhookCall->id]);

        return response()->json(['message' => 'OK'], 200);
    }

    protected function verifySignature(string $payload, ?string $signature): bool
    {
        $secret = config('dlocal.webhook_secret');
        if (empty($secret) || empty($signature)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return str_contains($signature, $expectedSignature);
    }
}

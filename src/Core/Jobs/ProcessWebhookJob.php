<?php

namespace Nandocdev\Dlocal\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Nandocdev\Dlocal\Core\Models\WebhookCall;
use Nandocdev\Dlocal\Payments\Services\TransactionService;
use Exception;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public WebhookCall $webhookCall) {}

    public function handle(TransactionService $transactionService): void
    {
        try {
            $payload = $this->webhookCall->payload;
            $status = $payload['status'] ?? null;
            $transactionId = $payload['id'] ?? null;

            if ($transactionId && $status) {
                $transactionService->handleWebhook($transactionId, $status, $payload);
            }

            $this->webhookCall->update([
                'status' => 'PROCESSED',
                'processed_at' => now()
            ]);
        } catch (Exception $e) {
            $this->webhookCall->update([
                'status' => 'FAILED',
                'error_message' => $e->getMessage()
            ]);
            throw $e; // Re-throw para la cola
        }
    }
}

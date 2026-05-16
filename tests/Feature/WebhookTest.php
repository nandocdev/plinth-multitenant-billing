<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Nandocdev\Dlocal\Core\Models\WebhookCall;
use Nandocdev\Dlocal\Core\Jobs\ProcessWebhookJob;
use Nandocdev\Dlocal\Payments\Models\Customer;
use Nandocdev\Dlocal\Payments\Models\Order;
use Nandocdev\Dlocal\Payments\Models\Transaction;
use Nandocdev\Dlocal\Core\Enums\TransactionStatus;

it('rejects webhooks with invalid signatures', function () {
    Log::shouldReceive('warning')->once();
    
    $payload = ['id' => 'tx_123', 'status' => 'PAID'];
    
    $response = $this->postJson('/api/dlocal/webhooks', $payload, [
        'Authorization' => 'V21-HMAC-SHA256 Signature=invalid_hash'
    ]);

    $response->assertStatus(401)
             ->assertJson(['error' => 'Invalid signature']);
});

it('stores webhook and dispatches job on valid signature', function () {
    Log::shouldReceive('info')->once();
    Queue::fake();
    
    $payload = json_encode(['id' => 'tx_123', 'status' => 'PAID']);
    $secret = config('dlocal.webhook_secret');
    $hash = hash_hmac('sha256', $payload, $secret);
    $signature = "V21-HMAC-SHA256 Signature={$hash}";
    
    $response = $this->call('POST', '/api/dlocal/webhooks', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_AUTHORIZATION' => $signature,
    ], $payload);

    $response->assertStatus(200)->assertJson(['message' => 'OK']);
    
    // Assert WebhookCall was created
    $webhookCall = WebhookCall::first();
    expect($webhookCall)->not->toBeNull()
        ->and($webhookCall->status)->toBe('PENDING');

    // Assert Job was dispatched
    Queue::assertPushed(ProcessWebhookJob::class, function ($job) use ($webhookCall) {
        return $job->webhookCall->id === $webhookCall->id;
    });
});

it('processes the job asynchronously', function () {
    $customer = Customer::create(['tenant_id' => 1, 'name' => 'John', 'email' => 'john@ex.com']);
    $order = Order::create(['tenant_id' => 1, 'customer_id' => $customer->id, 'amount' => 100, 'currency' => 'USD']);
    
    $transaction = Transaction::create([
        'tenant_id' => 1,
        'order_id' => $order->id,
        'dlocal_id' => 'tx_123',
        'amount' => 100.00,
        'currency' => 'USD',
        'country' => 'US',
        'status' => TransactionStatus::PENDING,
    ]);
    
    $webhookCall = WebhookCall::create([
        'payload' => ['id' => 'tx_123', 'status' => 'PAID'],
        'event_type' => 'PAID',
        'status' => 'PENDING',
    ]);
    
    $job = new ProcessWebhookJob($webhookCall);
    $job->handle(app(\Nandocdev\Dlocal\Payments\Services\TransactionService::class));
    
    expect($transaction->fresh()->status)->toBe(TransactionStatus::PAID);
    
    $ledger = \Nandocdev\Dlocal\Core\Models\LedgerEntry::where('reference_id', $transaction->id)->first();
    expect($ledger)->not->toBeNull()
        ->and($ledger->type)->toBe('CREDIT');
        
    expect($webhookCall->fresh()->status)->toBe('PROCESSED');
});

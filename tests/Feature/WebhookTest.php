<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);
use Plinth\MultiTenantBilling\Core\Models\WebhookCall;
use Plinth\MultiTenantBilling\Core\Jobs\ProcessWebhookJob;
use Plinth\MultiTenantBilling\Payments\Models\Customer;
use Plinth\MultiTenantBilling\Payments\Models\Order;
use Plinth\MultiTenantBilling\Payments\Models\Transaction;
use Plinth\MultiTenantBilling\Core\Enums\TransactionStatus;
use Plinth\MultiTenantBilling\Core\Models\TenantPaymentProvider;

it('rejects webhooks with invalid signatures for dlocal', function () {
    TenantPaymentProvider::create([
        'tenant_id' => 1,
        'provider' => 'dlocal',
        'credentials' => ['secret_key' => 'dlocal_secret'],
        'status' => 'active'
    ]);

    Log::shouldReceive('warning')->once();
    Log::shouldIgnoreMissing();
    
    $payload = ['id' => 'tx_123', 'status' => 'PAID'];
    
    $response = $this->postJson('/api/dlocal/webhooks/1', $payload, [
        'Authorization' => 'V21-HMAC-SHA256 Signature=invalid_hash'
    ]);

    $response->assertStatus(401)
             ->assertJson(['error' => 'Invalid signature']);
});

it('rejects webhooks with invalid signatures for stripe', function () {
    TenantPaymentProvider::create([
        'tenant_id' => 1,
        'provider' => 'stripe',
        'credentials' => ['webhook_secret' => 'stripe_secret'],
        'status' => 'active'
    ]);

    Log::shouldReceive('warning')->once();
    Log::shouldIgnoreMissing();
    
    $payload = ['id' => 'evt_123', 'type' => 'payment_intent.succeeded'];
    
    $response = $this->postJson('/api/stripe/webhooks/1', $payload, [
        'Stripe-Signature' => 't=123,v1=invalid_hash'
    ]);

    $response->assertStatus(401)
             ->assertJson(['error' => 'Invalid signature']);
});

it('stores dlocal webhook and dispatches job on valid signature', function () {
    TenantPaymentProvider::create([
        'tenant_id' => 1,
        'provider' => 'dlocal',
        'credentials' => ['secret_key' => 'dlocal_secret'],
        'status' => 'active'
    ]);

    Log::shouldReceive('info')->once();
    Log::shouldIgnoreMissing();
    Queue::fake();
    
    $payload = json_encode(['id' => 'tx_123', 'status' => 'PAID']);
    $secret = 'dlocal_secret';
    $hash = hash_hmac('sha256', $payload, $secret);
    $signature = "V21-HMAC-SHA256 Signature={$hash}";
    
    $response = $this->call('POST', '/api/dlocal/webhooks/1', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_AUTHORIZATION' => $signature,
    ], $payload);

    $response->assertStatus(200)->assertJson(['message' => 'OK']);
    
    $webhookCall = WebhookCall::first();
    expect($webhookCall)->not->toBeNull()
        ->and($webhookCall->status)->toBe('PENDING');

    Queue::assertPushed(ProcessWebhookJob::class);
});

it('stores stripe webhook and dispatches job on valid signature', function () {
    TenantPaymentProvider::create([
        'tenant_id' => 2,
        'provider' => 'stripe',
        'credentials' => ['webhook_secret' => 'stripe_secret'],
        'status' => 'active'
    ]);

    Log::shouldReceive('info')->once();
    Log::shouldIgnoreMissing();
    Queue::fake();
    
    $payload = json_encode(['id' => 'evt_123', 'type' => 'payment_intent.succeeded']);
    $secret = 'stripe_secret';
    $timestamp = time();
    $signedPayload = "{$timestamp}.{$payload}";
    $hash = hash_hmac('sha256', $signedPayload, $secret);
    $signature = "t={$timestamp},v1={$hash}";
    
    $response = $this->call('POST', '/api/stripe/webhooks/2', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => $signature,
    ], $payload);

    $response->assertStatus(200)->assertJson(['message' => 'OK']);
    
    $webhookCall = WebhookCall::first();
    expect($webhookCall)->not->toBeNull()
        ->and($webhookCall->status)->toBe('PENDING')
        ->and($webhookCall->event_type)->toBe('payment_intent.succeeded');

    Queue::assertPushed(ProcessWebhookJob::class);
});

it('processes the job asynchronously', function () {
    $customer = Customer::create(['tenant_id' => 1, 'name' => 'John', 'email' => 'john@ex.com']);
    $order = Order::create(['tenant_id' => 1, 'customer_id' => $customer->id, 'amount' => 100, 'currency' => 'USD']);
    
    $transaction = Transaction::create([
        'tenant_id' => 1,
        'order_id' => $order->id,
        'provider_id' => 'tx_123',
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
    $job->handle(app(\Plinth\MultiTenantBilling\Payments\Services\TransactionService::class));
    
    expect($transaction->fresh()->status)->toBe(TransactionStatus::PAID);
    
    $ledger = \Plinth\MultiTenantBilling\Core\Models\LedgerEntry::where('reference_id', $transaction->id)->first();
    expect($ledger)->not->toBeNull()
        ->and($ledger->type)->toBe('CREDIT');
        
    expect($webhookCall->fresh()->status)->toBe('PROCESSED');
});

<?php

use Plinth\MultiTenantBilling\Providers\Dlocal\DlocalPaymentGateway;
use Plinth\MultiTenantBilling\Core\Client\DlocalClient;
use Plinth\MultiTenantBilling\Payments\Models\Order;
use Plinth\MultiTenantBilling\Payments\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

it('verifies a valid dlocal webhook signature', function () {
    $secret = 'secret_key';
    $client = new DlocalClient('login', 'trans_key', $secret, 'sandbox');
    $gateway = new DlocalPaymentGateway($client, ['secret_key' => $secret]);

    $payload = json_encode(['id' => 'tx_123', 'status' => 'PAID']);
    config(['dlocal.webhook_secret' => $secret]);
    
    $hash = hash_hmac('sha256', $payload, $secret);
    $signature = "V21-HMAC-SHA256 Signature={$hash}";
    
    $request = Request::create('/webhooks', 'POST', [], [], [], [
        'HTTP_AUTHORIZATION' => $signature
    ], $payload);
    
    expect($gateway->verifyWebhook($request))->toBeTrue();
});

it('processes a dlocal payment', function () {
    $client = new DlocalClient('login', 'trans_key', 'secret_key', 'sandbox');
    $gateway = new DlocalPaymentGateway($client);

    Http::fake([
        'sandbox.dlocal.com/payments' => Http::response([
            'id' => 'dlocal_tx_123',
            'status' => 'PAID'
        ], 200)
    ]);

    $customer = Customer::create([
        'tenant_id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com'
    ]);

    $order = Order::create([
        'tenant_id' => 1,
        'customer_id' => $customer->id,
        'amount' => 100.00,
        'currency' => 'USD'
    ]);

    $result = $gateway->processPayment($order, ['type' => 'CARD']);

    expect($result)->toBeArray()
        ->and($result['transaction_id'])->toBe('dlocal_tx_123')
        ->and($result['status'])->toBe('PAID');
});

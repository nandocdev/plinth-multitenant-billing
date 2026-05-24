<?php

use Plinth\MultiTenantBilling\Providers\Stripe\StripePaymentGateway;
use Plinth\MultiTenantBilling\Core\Client\StripeClient;
use Plinth\MultiTenantBilling\Payments\Models\Order;
use Plinth\MultiTenantBilling\Payments\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

it('verifies a valid stripe webhook signature', function () {
    $secret = 'whsec_test_secret';
    $client = new StripeClient('sk_test_key');
    $gateway = new StripePaymentGateway($client, ['webhook_secret' => $secret]);

    $payload = json_encode([
        'id' => 'evt_123',
        'type' => 'payment_intent.succeeded'
    ]);
    
    $timestamp = time();
    $signedPayload = "{$timestamp}.{$payload}";
    $hash = hash_hmac('sha256', $signedPayload, $secret);
    $signature = "t={$timestamp},v1={$hash}";
    
    $request = Request::create('/webhooks', 'POST', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $signature
    ], $payload);
    
    expect($gateway->verifyWebhook($request))->toBeTrue();
});

it('processes a stripe payment', function () {
    $client = new StripeClient('sk_test_key');
    $gateway = new StripePaymentGateway($client);

    Http::fake([
        'api.stripe.com/v1/payment_intents' => Http::response([
            'id' => 'pi_123',
            'status' => 'succeeded'
        ], 200)
    ]);

    $customer = Customer::create([
        'tenant_id' => 1,
        'name' => 'Stripe User',
        'email' => 'stripe@example.com'
    ]);

    $order = Order::create([
        'tenant_id' => 1,
        'customer_id' => $customer->id,
        'amount' => 50.00,
        'currency' => 'USD'
    ]);

    $result = $gateway->processPayment($order, ['payment_method' => 'pm_card_visa']);

    expect($result)->toBeArray()
        ->and($result['transaction_id'])->toBe('pi_123')
        ->and($result['status'])->toBe('PAID');
});

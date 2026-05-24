<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Plinth\MultiTenantBilling\Payments\Services\PaymentProcessor;

uses(RefreshDatabase::class);
use Plinth\MultiTenantBilling\Payments\Models\Customer;
use Plinth\MultiTenantBilling\Payments\Models\Order;
use Plinth\MultiTenantBilling\Core\Enums\TransactionStatus;
use Plinth\MultiTenantBilling\Core\Models\TenantPaymentProvider;

it('creates a payment correctly', function () {
    TenantPaymentProvider::create([
        'tenant_id' => 1,
        'provider' => 'dlocal',
        'credentials' => [
            'login' => 'test',
            'trans_key' => 'test',
            'secret_key' => 'test',
            'environment' => 'sandbox'
        ],
        'status' => 'active'
    ]);

    Http::fake([
        'sandbox.dlocal.com/payments' => Http::response(['id' => 'tx_123', 'status' => 'PENDING'], 200)
    ]);
    
    $processor = app(PaymentProcessor::class);
    
    $customer = Customer::create(['tenant_id' => 1, 'name' => 'John', 'email' => 'john@ex.com']);
    $order = Order::create(['tenant_id' => 1, 'customer_id' => $customer->id, 'amount' => 100, 'currency' => 'USD']);
    
    $transaction = $processor->createPayin($order, ['type' => 'CARD']);
    
    expect($transaction->provider_id)->toBe('tx_123')
        ->and($transaction->status)->toBe(TransactionStatus::PENDING);
});

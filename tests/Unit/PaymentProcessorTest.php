<?php

use Illuminate\Support\Facades\Http;
use Plinth\MultiTenantBilling\Payments\Services\PaymentProcessor;
use Plinth\MultiTenantBilling\Payments\Models\Customer;
use Plinth\MultiTenantBilling\Payments\Models\Order;
use Plinth\MultiTenantBilling\Core\Enums\TransactionStatus;

it('creates a payment correctly', function () {
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

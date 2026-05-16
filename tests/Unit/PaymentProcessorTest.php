<?php

use Illuminate\Support\Facades\Http;
use Nandocdev\Dlocal\Payments\Services\PaymentProcessor;
use Nandocdev\Dlocal\Payments\Models\Customer;
use Nandocdev\Dlocal\Payments\Models\Order;
use Nandocdev\Dlocal\Core\Enums\TransactionStatus;

it('creates a payment correctly', function () {
    Http::fake([
        'sandbox.dlocal.com/payments' => Http::response(['id' => 'tx_123', 'status' => 'PENDING'], 200)
    ]);
    
    $processor = app(PaymentProcessor::class);
    
    $customer = Customer::create(['tenant_id' => 1, 'name' => 'John', 'email' => 'john@ex.com']);
    $order = Order::create(['tenant_id' => 1, 'customer_id' => $customer->id, 'amount' => 100, 'currency' => 'USD']);
    
    $transaction = $processor->createPayin($order, ['type' => 'CARD']);
    
    expect($transaction->dlocal_id)->toBe('tx_123')
        ->and($transaction->status)->toBe(TransactionStatus::PENDING);
});

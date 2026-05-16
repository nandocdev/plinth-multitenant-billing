<?php

use Plinth\MultiTenantBilling\Payments\Services\TransactionService;
use Plinth\MultiTenantBilling\Payments\Models\Transaction;
use Plinth\MultiTenantBilling\Payments\Models\Order;
use Plinth\MultiTenantBilling\Payments\Models\Customer;
use Plinth\MultiTenantBilling\Core\Models\LedgerEntry;
use Plinth\MultiTenantBilling\Core\Enums\TransactionStatus;

it('updates transaction status and records ledger entry on paid', function () {
    $service = new TransactionService();

    $customer = Customer::create(['tenant_id' => 1, 'name' => 'John', 'email' => 'john@ex.com']);
    $order = Order::create(['tenant_id' => 1, 'customer_id' => $customer->id, 'amount' => 100, 'currency' => 'USD']);
    
    $transaction = Transaction::create([
        'tenant_id' => 1,
        'order_id' => $order->id,
        'provider_id' => 'tx_paid_123',
        'amount' => 100.00,
        'currency' => 'USD',
        'country' => 'US',
        'status' => TransactionStatus::PENDING,
    ]);

    $service->handleWebhook('tx_paid_123', 'PAID', ['status' => 'PAID', 'id' => 'tx_paid_123']);

    $transaction = $transaction->fresh();
    expect($transaction->status)->toBe(TransactionStatus::PAID);

    $ledger = LedgerEntry::where('reference_id', $transaction->id)
        ->where('reference_type', Transaction::class)
        ->first();

    expect($ledger)->not->toBeNull()
        ->and($ledger->type)->toBe('CREDIT')
        ->and((float) $ledger->amount)->toBe(100.00);
});

it('records debit on refund status', function () {
    $service = new TransactionService();

    $customer = Customer::create(['tenant_id' => 1, 'name' => 'John', 'email' => 'john@ex.com']);
    $order = Order::create(['tenant_id' => 1, 'customer_id' => $customer->id, 'amount' => 100, 'currency' => 'USD']);
    
    $transaction = Transaction::create([
        'tenant_id' => 1,
        'order_id' => $order->id,
        'provider_id' => 'tx_refund_123',
        'amount' => 100.00,
        'currency' => 'USD',
        'country' => 'US',
        'status' => TransactionStatus::PAID,
    ]);

    $service->handleWebhook('tx_refund_123', 'REFUNDED', ['status' => 'REFUNDED', 'id' => 'tx_refund_123']);

    expect($transaction->fresh()->status)->toBe(TransactionStatus::REFUNDED);

    $ledger = LedgerEntry::where('reference_id', $transaction->id)
        ->where('type', 'DEBIT')
        ->first();

    expect($ledger)->not->toBeNull()
        ->and((float) $ledger->amount)->toBe(100.00);
});

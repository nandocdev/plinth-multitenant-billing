<?php

namespace Plinth\MultiTenantBilling\Payments\Services;

use Plinth\MultiTenantBilling\Contracts\PaymentProvider;
use Plinth\MultiTenantBilling\Payments\Models\Order;
use Plinth\MultiTenantBilling\Payments\Models\Transaction;

class PaymentProcessor
{
    public function __construct(protected PaymentProvider $provider) {}

    public function createPayin(Order $order, array $paymentMethodData)
    {
        $idempotencyKey = 'order_payin_' . $order->id;
        $response = $this->provider->processPayment($order, $paymentMethodData, $idempotencyKey);
            
        return Transaction::create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'provider_id' => $response['transaction_id'],
            'amount' => $order->amount,
            'currency' => $order->currency,
            'country' => 'US', // default
            'status' => $response['status']
        ]);
    }
}

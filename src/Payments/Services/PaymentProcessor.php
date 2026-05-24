<?php

namespace Plinth\MultiTenantBilling\Payments\Services;

use Plinth\MultiTenantBilling\Core\Factories\PaymentProviderFactory;
use Plinth\MultiTenantBilling\Payments\Models\Order;
use Plinth\MultiTenantBilling\Payments\Models\Transaction;

class PaymentProcessor
{
    public function __construct(protected PaymentProviderFactory $factory) {}

    public function createPayin(Order $order, array $paymentMethodData)
    {
        $provider = $this->factory->makePaymentProvider($order->tenant_id);
        
        $idempotencyKey = 'order_payin_' . $order->id;
        $response = $provider->processPayment($order, $paymentMethodData, $idempotencyKey);
            
        return Transaction::create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'provider_id' => $response['transaction_id'],
            'amount' => $order->amount,
            'currency' => $order->currency,
            'country' => $order->country ?? 'US',
            'status' => $response['status']
        ]);
    }
}

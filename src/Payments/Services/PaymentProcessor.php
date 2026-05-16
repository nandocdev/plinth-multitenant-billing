<?php

namespace Nandocdev\Dlocal\Payments\Services;

use Nandocdev\Dlocal\Contracts\PaymentProvider;
use Nandocdev\Dlocal\Payments\Models\Order;
use Nandocdev\Dlocal\Payments\Models\Transaction;

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
            'dlocal_id' => $response['transaction_id'],
            'amount' => $order->amount,
            'currency' => $order->currency,
            'country' => 'US', // default
            'status' => $response['status']
        ]);
    }
}

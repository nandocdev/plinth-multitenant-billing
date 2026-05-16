<?php

namespace Nandocdev\Dlocal\Core\Gateways;

use Nandocdev\Dlocal\Contracts\PaymentProvider;
use Nandocdev\Dlocal\Core\Client\DlocalClient;
use Nandocdev\Dlocal\Payments\Models\Order;
use Exception;

class DlocalPaymentGateway implements PaymentProvider
{
    public function __construct(protected DlocalClient $client) {}

    public function processPayment(Order $order, array $paymentMethodData, ?string $idempotencyKey = null): array
    {
        $payload = [
            'amount' => $order->amount,
            'currency' => $order->currency,
            'country' => 'US', // default para el test
            'payment_method' => $paymentMethodData
        ];
        
        $headers = [];
        if ($idempotencyKey) {
            $headers['X-Idempotency-Key'] = $idempotencyKey;
        }
        
        $response = $this->client->request('POST', '/payments', $payload, $headers)
            ->post('/payments', $payload);
            
        if ($response->successful()) {
            return [
                'transaction_id' => $response->json('id'),
                'status' => $response->json('status', 'PENDING')
            ];
        }
        
        throw new Exception('Dlocal API Error (Payments): ' . $response->body());
    }
}

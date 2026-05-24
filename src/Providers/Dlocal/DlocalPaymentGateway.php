<?php

namespace Plinth\MultiTenantBilling\Providers\Dlocal;

use Plinth\MultiTenantBilling\Contracts\PaymentProvider;
use Plinth\MultiTenantBilling\Core\Client\DlocalClient;
use Plinth\MultiTenantBilling\Payments\Models\Order;
use Illuminate\Http\Request;
use Exception;

class DlocalPaymentGateway implements PaymentProvider
{
    protected string $webhookSecret;

    public function __construct(
        protected DlocalClient $client,
        array $config = []
    ) {
        $this->webhookSecret = $config['webhook_secret'] ?? $config['secret_key'] ?? '';
    }

    public function createCheckout(array $payload): array
    {
        // Dummy implementation for creating a checkout session
        return [
            'id' => 'checkout_dummy_id',
            'url' => 'https://checkout.dlocal.com/dummy',
        ];
    }

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

    public function refund(string $transactionId): array
    {
        // Dummy implementation for refund
        return [
            'refund_id' => 'refund_dummy_id',
            'status' => 'PENDING',
        ];
    }

    public function tokenize(array $cardData): array
    {
        // Dummy implementation for tokenization
        return [
            'token' => 'tok_dummy_token',
        ];
    }

    public function verifyWebhook(Request $request): bool
    {
        $payload = $request->getContent();
        $signature = $request->header('Authorization');

        if (empty($this->webhookSecret) || empty($signature)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);
        return str_contains($signature, $expectedSignature);
    }
}

<?php

namespace Plinth\MultiTenantBilling\Providers\Stripe;

use Plinth\MultiTenantBilling\Contracts\PaymentProvider;
use Plinth\MultiTenantBilling\Core\Client\StripeClient;
use Plinth\MultiTenantBilling\Payments\Models\Order;
use Plinth\MultiTenantBilling\Core\Enums\TransactionStatus;
use Illuminate\Http\Request;
use Exception;

class StripePaymentGateway implements PaymentProvider
{
    protected string $webhookSecret;

    public function __construct(
        protected StripeClient $client,
        array $config = []
    ) {
        $this->webhookSecret = $config['webhook_secret'] ?? '';
    }

    public function createCheckout(array $payload): array
    {
        $response = $this->client->request()->post('/checkout/sessions', $payload);

        if ($response->successful()) {
            return [
                'id' => $response->json('id'),
                'url' => $response->json('url'),
            ];
        }

        throw new Exception('Stripe API Error (Checkout): ' . $response->body());
    }

    public function processPayment(Order $order, array $paymentMethodData, ?string $idempotencyKey = null): array
    {
        $payload = [
            'amount' => (int) ($order->amount * 100), // Stripe uses cents
            'currency' => strtolower($order->currency),
            'payment_method' => $paymentMethodData['payment_method'] ?? null,
            'confirm' => 'true',
            'automatic_payment_methods' => [
                'enabled' => 'true',
                'allow_redirects' => 'never',
            ],
        ];

        $request = $this->client->request();

        if ($idempotencyKey) {
            $request->withHeaders(['Idempotency-Key' => $idempotencyKey]);
        }

        $response = $request->post('/payment_intents', $payload);

        if ($response->successful()) {
            return [
                'transaction_id' => $response->json('id'),
                'status' => $this->mapStatus($response->json('status')),
            ];
        }

        throw new Exception('Stripe API Error (PaymentIntents): ' . $response->body());
    }

    public function refund(string $transactionId): array
    {
        $payload = [
            'payment_intent' => $transactionId,
        ];

        $response = $this->client->request()->post('/refunds', $payload);

        if ($response->successful()) {
            return [
                'refund_id' => $response->json('id'),
                'status' => $this->mapStatus($response->json('status')),
            ];
        }

        throw new Exception('Stripe API Error (Refunds): ' . $response->body());
    }

    public function tokenize(array $cardData): array
    {
        $response = $this->client->request()->post('/tokens', ['card' => $cardData]);

        if ($response->successful()) {
            return [
                'token' => $response->json('id'),
            ];
        }

        throw new Exception('Stripe API Error (Tokenization): ' . $response->body());
    }

    public function verifyWebhook(Request $request): bool
    {
        $signature = $request->header('Stripe-Signature');
        $payload = $request->getContent();

        if (empty($this->webhookSecret) || empty($signature)) {
            return false;
        }

        // Basic verification logic for Stripe webhooks without official SDK
        // format: t=timestamp,v1=signature,v0=signature
        if (!preg_match('/t=(\d+)/', $signature, $matches)) {
            return false;
        }
        $timestamp = $matches[1];

        if (!preg_match('/v1=([a-f0-9]+)/', $signature, $matches)) {
            return false;
        }
        $v1Signature = $matches[1];

        $signedPayload = "{$timestamp}.{$payload}";
        $expectedSignature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        return hash_equals($expectedSignature, $v1Signature);
    }

    protected function mapStatus(?string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'succeeded' => TransactionStatus::PAID->value,
            'processing' => TransactionStatus::PENDING->value,
            'requires_payment_method', 'requires_confirmation', 'requires_action' => TransactionStatus::PENDING->value,
            'canceled' => TransactionStatus::CANCELED->value,
            'failed' => TransactionStatus::FAILED->value,
            default => TransactionStatus::PENDING->value,
        };
    }
}

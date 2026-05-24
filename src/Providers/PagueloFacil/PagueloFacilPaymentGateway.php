<?php

namespace Plinth\MultiTenantBilling\Providers\PagueloFacil;

use Plinth\MultiTenantBilling\Contracts\PaymentProvider;
use Plinth\MultiTenantBilling\Core\Client\PagueloFacilClient;
use Plinth\MultiTenantBilling\Payments\Models\Order;
use Plinth\MultiTenantBilling\Core\Enums\TransactionStatus;
use Illuminate\Http\Request;
use Exception;

class PagueloFacilPaymentGateway implements PaymentProvider
{
    public function __construct(
        protected PagueloFacilClient $client,
        array $config = []
    ) {}

    public function createCheckout(array $payload): array
    {
        // PagueloFacil Hosted Checkout works by redirecting to LinkDeamon.cfm
        $query = [
            'CCLW' => $this->client->getCclw(),
            'CMTN' => number_format($payload['amount'], 2, '.', ''),
            'CDSC' => $payload['description'] ?? 'Payment',
            'RETURN_URL' => isset($payload['success_url']) ? $this->client->encodeUrl($payload['success_url']) : null,
            'PARM_1' => $payload['order_id'] ?? null,
        ];

        $checkoutUrl = $this->client->getBaseUrl() . '/LinkDeamon.cfm?' . http_build_query(array_filter($query));

        return [
            'id' => null, // PagueloFacil LinkDeamon doesn't return a session ID upfront
            'url' => $checkoutUrl,
        ];
    }

    public function processPayment(Order $order, array $paymentMethodData, ?string $idempotencyKey = null): array
    {
        // PagueloFacil direct payments are usually handled via their REST API (api.pfserver.net)
        // or using their tokenization system.
        throw new Exception('Direct payments not yet implemented for PagueloFacil provider.');
    }

    public function refund(string $transactionId): array
    {
        // PagueloFacil REVERSE operation
        $response = $this->client->request()->post('/api/rest/reverse', [
            'CodOper' => $transactionId,
        ]);

        if ($response->successful()) {
            return [
                'refund_id' => $response->json('CodOper'),
                'status' => TransactionStatus::REFUNDED->value,
            ];
        }

        throw new Exception('PagueloFacil API Error (Refund): ' . $response->body());
    }

    public function tokenize(array $cardData): array
    {
        throw new Exception('Tokenization not yet implemented for PagueloFacil provider.');
    }

    public function verifyWebhook(Request $request): bool
    {
        // PagueloFacil doesn't send a cryptographic signature in the default webhook.
        // It's recommended to validate the CCLW and the Status from the POST data.
        $cclw = $request->input('CCLW');
        $status = $request->input('Estado');

        if (empty($cclw) || $cclw !== $this->client->getCclw()) {
            return false;
        }

        return !empty($status);
    }

    public function mapStatus(string $pfStatus): string
    {
        return match (strtoupper($pfStatus)) {
            'COMPLETED', 'APPROVED' => TransactionStatus::PAID->value,
            'DECLINED', 'REJECTED' => TransactionStatus::REJECTED->value,
            'EXPIRED' => TransactionStatus::EXPIRED->value,
            'PENDING' => TransactionStatus::PENDING->value,
            default => TransactionStatus::PENDING->value,
        };
    }
}

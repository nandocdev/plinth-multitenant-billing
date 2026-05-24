<?php

namespace Plinth\MultiTenantBilling\Providers\Stripe;

use Plinth\MultiTenantBilling\Contracts\BillingProvider;
use Plinth\MultiTenantBilling\Core\Client\StripeClient;
use Plinth\MultiTenantBilling\Billing\Models\Plan;
use Exception;

class StripeBillingGateway implements BillingProvider
{
    public function __construct(protected StripeClient $client) {}

    public function createSubscription(Plan $plan, array $payerData): array
    {
        $payload = [
            'customer' => $payerData['customer_id'] ?? null,
            'items' => [
                ['price' => $plan->provider_plan_id],
            ],
        ];

        $response = $this->client->request()->post('/subscriptions', $payload);

        if ($response->successful()) {
            return [
                'subscription_id' => $response->json('id'),
                'status' => strtoupper($response->json('status')),
            ];
        }

        throw new Exception('Stripe API Error (Subscriptions): ' . $response->body());
    }
}

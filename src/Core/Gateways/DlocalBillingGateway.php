<?php

namespace Nandocdev\Dlocal\Core\Gateways;

use Nandocdev\Dlocal\Contracts\BillingProvider;
use Nandocdev\Dlocal\Core\Client\DlocalClient;
use Nandocdev\Dlocal\Billing\Models\Plan;
use Exception;

class DlocalBillingGateway implements BillingProvider
{
    public function __construct(protected DlocalClient $client) {}

    public function createSubscription(Plan $plan, array $payerData): array
    {
        $payload = [
             'plan_id' => $plan->dlocal_plan_id,
             'payer' => $payerData
        ];

        $response = $this->client->request('POST', '/subscriptions', $payload)
            ->post('/subscriptions', $payload);
        
        if ($response->successful()) {
            return [
                'subscription_id' => $response->json('id'),
                'status' => $response->json('status', 'ACTIVE')
            ];
        }
        
        throw new Exception('Dlocal API Error (Billing): ' . $response->body());
    }
}

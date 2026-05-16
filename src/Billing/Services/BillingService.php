<?php

namespace Plinth\MultiTenantBilling\Billing\Services;

use Plinth\MultiTenantBilling\Contracts\BillingProvider;
use Plinth\MultiTenantBilling\Billing\Models\Plan;
use Plinth\MultiTenantBilling\Billing\Models\Subscription;

class BillingService
{
    public function __construct(protected BillingProvider $provider) {}

    public function createSubscription(int $tenantId, Plan $plan, array $payerData)
    {
        $response = $this->provider->createSubscription($plan, $payerData);
        
        return Subscription::create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->id,
            'provider_subscription_id' => $response['subscription_id'],
            'status' => $response['status'],
        ]);
    }
}

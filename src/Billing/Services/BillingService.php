<?php

namespace Nandocdev\Dlocal\Billing\Services;

use Nandocdev\Dlocal\Contracts\BillingProvider;
use Nandocdev\Dlocal\Billing\Models\Plan;
use Nandocdev\Dlocal\Billing\Models\Subscription;

class BillingService
{
    public function __construct(protected BillingProvider $provider) {}

    public function createSubscription(int $tenantId, Plan $plan, array $payerData)
    {
        $response = $this->provider->createSubscription($plan, $payerData);
        
        return Subscription::create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->id,
            'dlocal_subscription_id' => $response['subscription_id'],
            'status' => $response['status'],
        ]);
    }
}

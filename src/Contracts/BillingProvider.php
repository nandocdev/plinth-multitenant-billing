<?php

namespace Plinth\MultiTenantBilling\Contracts;

use Plinth\MultiTenantBilling\Billing\Models\Plan;

interface BillingProvider
{
    /**
     * Crea una suscripción en el Gateway de pagos.
     *
     * @param Plan $plan
     * @param array $payerData
     * @return array Debe contener al menos 'subscription_id' y 'status'
     */
    public function createSubscription(Plan $plan, array $payerData): array;
}

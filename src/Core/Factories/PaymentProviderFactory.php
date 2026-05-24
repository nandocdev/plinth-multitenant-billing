<?php

namespace Plinth\MultiTenantBilling\Core\Factories;

use Plinth\MultiTenantBilling\Contracts\PaymentProvider;
use Plinth\MultiTenantBilling\Contracts\BillingProvider;
use Plinth\MultiTenantBilling\Core\Models\TenantPaymentProvider;
use Plinth\MultiTenantBilling\Core\Client\StripeClient;
use Plinth\MultiTenantBilling\Core\Client\DlocalClient;
use Plinth\MultiTenantBilling\Providers\Stripe\StripePaymentGateway;
use Plinth\MultiTenantBilling\Providers\Stripe\StripeBillingGateway;
use Plinth\MultiTenantBilling\Providers\Dlocal\DlocalPaymentGateway;
use Plinth\MultiTenantBilling\Providers\Dlocal\DlocalBillingGateway;
use Plinth\MultiTenantBilling\Core\Client\PagueloFacilClient;
use Plinth\MultiTenantBilling\Providers\PagueloFacil\PagueloFacilPaymentGateway;
use Exception;

class PaymentProviderFactory
{
    /**
     * Resolve PaymentProvider by tenant ID.
     */
    public function makePaymentProvider(int|string $tenantId): PaymentProvider
    {
        $config = $this->getTenantConfig($tenantId);
        return $this->buildPaymentProvider($config->provider, $config->credentials);
    }

    /**
     * Resolve BillingProvider by tenant ID.
     */
    public function makeBillingProvider(int|string $tenantId): BillingProvider
    {
        $config = $this->getTenantConfig($tenantId);
        return $this->buildBillingProvider($config->provider, $config->credentials);
    }

    /**
     * Build PaymentProvider by provider name and credentials.
     * Useful for webhooks where tenant context might be resolved differently.
     */
    public function buildPaymentProvider(string $provider, array $credentials): PaymentProvider
    {
        return match (strtolower($provider)) {
            'stripe' => new StripePaymentGateway(
                new StripeClient($credentials['secret_key'] ?? ''),
                $credentials
            ),
            'dlocal' => new DlocalPaymentGateway(
                new DlocalClient(
                    $credentials['login'] ?? '',
                    $credentials['trans_key'] ?? '',
                    $credentials['secret_key'] ?? '',
                    $credentials['environment'] ?? 'sandbox'
                ),
                $credentials
            ),
            'paguelofacil' => new PagueloFacilPaymentGateway(
                new PagueloFacilClient(
                    $credentials['CCLW'] ?? '',
                    $credentials['environment'] ?? 'sandbox'
                ),
                $credentials
            ),
            default => throw new Exception("Unsupported payment provider: {$provider}"),
        };
    }

    /**
     * Build BillingProvider by provider name and credentials.
     */
    public function buildBillingProvider(string $provider, array $credentials): BillingProvider
    {
        return match (strtolower($provider)) {
            'stripe' => new StripeBillingGateway(
                new StripeClient($credentials['secret_key'] ?? '')
            ),
            'dlocal' => new DlocalBillingGateway(
                new DlocalClient(
                    $credentials['login'] ?? '',
                    $credentials['trans_key'] ?? '',
                    $credentials['secret_key'] ?? '',
                    $credentials['environment'] ?? 'sandbox'
                )
            ),
            default => throw new Exception("Unsupported billing provider: {$provider}"),
        };
    }

    protected function getTenantConfig(int|string $tenantId): TenantPaymentProvider
    {
        $config = TenantPaymentProvider::where('tenant_id', $tenantId)
            ->whereIn('status', ['active', 'ACTIVE'])
            ->first();

        if (!$config) {
            throw new Exception("No active payment provider found for tenant: {$tenantId}");
        }

        return $config;
    }
}

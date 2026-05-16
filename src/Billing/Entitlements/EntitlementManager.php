<?php

namespace Plinth\MultiTenantBilling\Billing\Entitlements;

use Illuminate\Support\Facades\Redis;

class EntitlementManager
{
    /**
     * Verifica si un feature está habilitado para un tenant.
     *
     * @param mixed $tenantId
     * @param string $feature
     * @return bool
     */
    public function hasFeature($tenantId, string $feature): bool
    {
        return (bool) Redis::sismember("tenant:{$tenantId}:features", $feature);
    }

    /**
     * Habilita un feature (entitlement) para un tenant.
     *
     * @param mixed $tenantId
     * @param string $feature
     * @return void
     */
    public function enable($tenantId, string $feature): void
    {
        Redis::sadd("tenant:{$tenantId}:features", $feature);
    }

    /**
     * Deshabilita un feature.
     *
     * @param mixed $tenantId
     * @param string $feature
     * @return void
     */
    public function disable($tenantId, string $feature): void
    {
        Redis::srem("tenant:{$tenantId}:features", $feature);
    }

    /**
     * Sincroniza todos los features habilitados (ej: al cambiar de plan).
     *
     * @param mixed $tenantId
     * @param array $features
     * @return void
     */
    public function sync($tenantId, array $features): void
    {
        $key = "tenant:{$tenantId}:features";
        Redis::del($key);
        if (!empty($features)) {
            Redis::sadd($key, ...$features);
        }
    }
}

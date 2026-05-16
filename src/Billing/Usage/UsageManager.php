<?php

namespace Plinth\MultiTenantBilling\Billing\Usage;

use Illuminate\Support\Facades\Redis;

class UsageManager
{
    /**
     * Incrementa un contador de uso de forma atómica en Redis.
     *
     * @param mixed $tenantId
     * @param string $feature
     * @param int $value
     * @return int Nuevo valor del contador
     */
    public function increment($tenantId, string $feature, int $value = 1): int
    {
        $key = $this->getCacheKey($tenantId, $feature);
        
        return (int) Redis::incrby($key, $value);
    }

    /**
     * Obtiene el uso actual para un tenant y feature.
     *
     * @param mixed $tenantId
     * @param string $feature
     * @return int
     */
    public function getCurrentUsage($tenantId, string $feature): int
    {
        return (int) Redis::get($this->getCacheKey($tenantId, $feature)) ?: 0;
    }

    /**
     * Resetea el contador (útil para nuevos periodos de facturación).
     *
     * @param mixed $tenantId
     * @param string $feature
     * @return void
     */
    public function reset($tenantId, string $feature): void
    {
        Redis::del($this->getCacheKey($tenantId, $feature));
    }

    protected function getCacheKey($tenantId, string $feature): string
    {
        return "tenant:{$tenantId}:usage:{$feature}";
    }

    /**
     * Reconstruye el caché de Redis desde el último snapshot de la base de datos.
     * Útil tras pérdida de datos en Redis o para reconciliación periódica.
     */
    public function rebuildCacheFromLastSnapshot($tenantId, string $feature): void
    {
        $lastSnapshot = UsageSnapshot::where('tenant_id', $tenantId)
            ->where('feature', $feature)
            ->orderByDesc('snapshot_at')
            ->first();

        if ($lastSnapshot) {
            Redis::set($this->getCacheKey($tenantId, $feature), $lastSnapshot->total_usage);
        } else {
            $this->reset($tenantId, $feature);
        }
    }
}

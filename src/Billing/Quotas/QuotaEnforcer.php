<?php

namespace Plinth\MultiTenantBilling\Billing\Quotas;

use Plinth\MultiTenantBilling\Billing\Usage\UsageManager;
use Illuminate\Support\Facades\Redis;

class QuotaEnforcer
{
    public function __construct(protected UsageManager $usage) {}

    /**
     * Verifica si un tenant tiene cuota disponible para una feature.
     *
     * @param mixed $tenantId
     * @param string $feature
     * @param int $required
     * @return bool
     */
    public function canConsume($tenantId, string $feature, int $required = 1): bool
    {
        $limit = $this->getLimit($tenantId, $feature);
        
        // Si el límite es -1 (unlimited), siempre puede consumir
        if ($limit === -1) {
            return true;
        }

        $current = $this->usage->getCurrentUsage($tenantId, $feature);

        return ($current + $required) <= $limit;
    }

    /**
     * Obtiene el límite configurado para un tenant. 
     * Prioriza Redis (cache) y luego debería consultar la suscripción.
     */
    public function getLimit($tenantId, string $feature): int
    {
        $cached = Redis::get("tenant:{$tenantId}:limit:{$feature}");
        
        if ($cached !== null) {
            return (int) $cached;
        }

        // TODO: Implementar lógica de recuperación desde la DB (Suscripción/Plan)
        return 0; 
    }

    /**
     * Setea el límite en caché.
     */
    public function setLimit($tenantId, string $feature, int $limit): void
    {
        Redis::set("tenant:{$tenantId}:limit:{$feature}", $limit);
    }
}

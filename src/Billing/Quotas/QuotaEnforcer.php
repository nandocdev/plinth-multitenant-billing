<?php

namespace Plinth\MultiTenantBilling\Billing\Quotas;

use Plinth\MultiTenantBilling\Billing\Usage\UsageManager;
use Plinth\MultiTenantBilling\Billing\Exceptions\QuotaExceededException;
use Illuminate\Support\Facades\Redis;

class QuotaEnforcer
{
    public function __construct(protected UsageManager $usage) {}

    /**
     * Consume una cantidad de cuota de forma atómica.
     * Implementa el patrón Increment-then-Check para evitar race conditions.
     *
     * @param mixed $tenantId
     * @param string $feature
     * @param int $required
     * @return int Nuevo valor de uso
     * 
     * @throws QuotaExceededException
     */
    public function consume($tenantId, string $feature, int $required = 1): int
    {
        $limit = $this->getLimit($tenantId, $feature);

        // Caso ILIMITADO: Solo incrementamos y retornamos
        if ($limit === -1) {
            return $this->usage->increment($tenantId, $feature, $required);
        }

        // Incrementamos primero de forma atómica
        $newUsage = $this->usage->increment($tenantId, $feature, $required);

        // Si excedemos el límite, revertimos y lanzamos excepción
        if ($newUsage > $limit) {
            $this->usage->increment($tenantId, $feature, -$required); // Rollback atómico
            throw QuotaExceededException::forFeature($feature, $limit);
        }

        return $newUsage;
    }

    /**
     * Verifica si un tenant tiene cuota disponible (No atómico, solo para UI/Consultas).
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

<?php

namespace Plinth\MultiTenantBilling\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Plinth\MultiTenantBilling\Billing\Usage\UsageManager;
use Plinth\MultiTenantBilling\Billing\Usage\UsageSnapshot;
use Illuminate\Support\Facades\Log;

class SyncUsageToDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param mixed $tenantId
     * @param string $feature
     */
    public function __construct(
        protected $tenantId,
        protected string $feature
    ) {}

    public function handle(UsageManager $usageManager): void
    {
        try {
            $currentUsage = $usageManager->getCurrentUsage($this->tenantId, $this->feature);

            UsageSnapshot::create([
                'tenant_id' => $this->tenantId,
                'feature' => $this->feature,
                'total_usage' => $currentUsage,
                'snapshot_at' => now(),
            ]);

            Log::debug("Usage synced to DB", [
                'tenant_id' => $this->tenantId,
                'feature' => $this->feature,
                'usage' => $currentUsage
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to sync usage to DB", [
                'tenant_id' => $this->tenantId,
                'feature' => $this->feature,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}

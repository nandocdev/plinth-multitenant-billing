<?php

namespace Plinth\MultiTenantBilling\Billing\Exceptions;

use Exception;

class QuotaExceededException extends Exception
{
    public static function forFeature(string $feature, int $limit): self
    {
        return new self("Quota exceeded for feature: {$feature}. Limit: {$limit}");
    }
}

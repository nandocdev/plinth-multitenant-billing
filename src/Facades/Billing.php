<?php

declare(strict_types=1);

namespace Plinth\MultiTenantBilling\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for Billing.
 */
class Billing extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'billing';
    }
}

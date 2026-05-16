<?php

declare(strict_types=1);

namespace Nandocdev\Dlocal\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for dLocal Client.
 * 
 * @see \Nandocdev\Dlocal\Client\DlocalClient
 */
class Dlocal extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'dlocal';
    }
}

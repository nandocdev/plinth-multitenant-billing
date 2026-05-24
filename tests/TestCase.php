<?php

declare(strict_types=1);

namespace Plinth\MultiTenantBilling\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Plinth\MultiTenantBilling\BillingServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            BillingServiceProvider::class,
        ];
    }
    
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('dlocal.login', 'test_login');
        $app['config']->set('dlocal.trans_key', 'test_key');
        $app['config']->set('dlocal.secret_key', 'test_secret');
        $app['config']->set('dlocal.environment', 'sandbox');
        $app['config']->set('dlocal.webhook_secret', 'test_webhook_secret');
        
        // Setup database for migrations
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../src/Database/migrations');
    }
}

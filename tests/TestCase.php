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
        
        // Run package migrations
        $migration1 = include __DIR__.'/../src/Database/migrations/2026_05_16_000001_create_billing_tables.php';
        $migration1->up();
        
        $migration2 = include __DIR__.'/../src/Database/migrations/2026_05_16_000002_create_payments_tables.php';
        $migration2->up();
        
        $migration3 = include __DIR__.'/../src/Database/migrations/2026_05_16_000003_create_ledger_entries_table.php';
        $migration3->up();
        
        $migration4 = include __DIR__.'/../src/Database/migrations/2026_05_16_000004_create_webhook_calls_table.php';
        $migration4->up();
        
        $migration5 = include __DIR__.'/../src/Database/migrations/2026_05_16_000005_create_additional_billing_tables.php';
        $migration5->up();
        
        $migration6 = include __DIR__.'/../src/Database/migrations/2026_05_16_000006_create_additional_payments_tables.php';
        $migration6->up();

        $migration7 = include __DIR__.'/../src/Database/migrations/2026_05_16_000007_create_tenant_payment_providers_table.php';
        $migration7->up();

        $migration8 = include __DIR__.'/../src/Database/migrations/2026_05_16_134351_create_usage_snapshots_table.php';
        $migration8->up();
    }
}

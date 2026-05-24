<?php

declare(strict_types=1);

namespace Plinth\MultiTenantBilling;

use Illuminate\Support\ServiceProvider;
use Plinth\MultiTenantBilling\Core\Factories\PaymentProviderFactory;

/**
 * Service provider for the Multi-Tenant Billing package.
 * 
 * Handles configuration merging, singleton registration, 
 * and publishing of assets, config, and migrations.
 */
class BillingServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     * 
     * Merges package configuration and registers the PaymentProviderFactory.
     * 
     * @return void
     */
    public function register(): void {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/Core/Config/dlocal.php',
            'billing'
        );

        // Register the Factory as a singleton
        $this->app->singleton(PaymentProviderFactory::class, function ($app) {
            return new PaymentProviderFactory();
        });
    }

    /**
     * Bootstrap any package services.
     * 
     * Handles publishing of config and migrations, and loads package routes.
     * 
     * @return void
     */
    public function boot(): void {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/Core/Config/dlocal.php' => config_path('billing.php'),
            ], 'billing-config');

            // Publish migrations using the standard publishes method
            $this->publishes([
                __DIR__ . '/Database/migrations' => database_path('migrations'),
            ], 'billing-migrations');
        }

        // Make package migrations available to the application without publishing
        $this->loadMigrationsFrom(__DIR__ . '/Database/migrations');

        // Load routes when available
        $this->loadRoutesFrom(__DIR__ . '/Routes/api.php');
    }
}

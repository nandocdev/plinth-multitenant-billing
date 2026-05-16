<?php

declare(strict_types=1);

namespace Nandocdev\Dlocal;

use Illuminate\Support\ServiceProvider;
use Nandocdev\Dlocal\Core\Client\DlocalClient;
use Nandocdev\Dlocal\Contracts\PaymentProvider;
use Nandocdev\Dlocal\Contracts\BillingProvider;
use Nandocdev\Dlocal\Core\Gateways\DlocalPaymentGateway;
use Nandocdev\Dlocal\Core\Gateways\DlocalBillingGateway;

/**
 * Service provider for the dLocal Laravel package.
 * 
 * Handles configuration merging, singleton registration, 
 * and publishing of assets, config, and migrations.
 */
class DlocalServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * 
     * Merges package configuration and registers the DlocalClient singleton.
     * 
     * @return void
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/Core/Config/dlocal.php', 'dlocal'
        );

        // Register the main class to use with the facade and for injection
        $this->app->singleton(DlocalClient::class, function ($app) {
            return new DlocalClient(
                config('dlocal.login', ''),
                config('dlocal.trans_key', ''),
                config('dlocal.secret_key', ''),
                config('dlocal.environment', 'sandbox')
            );
        });

        $this->app->alias(DlocalClient::class, 'dlocal');

        // Bind interfaces to Gateway implementations
        $this->app->bind(PaymentProvider::class, DlocalPaymentGateway::class);
        $this->app->bind(BillingProvider::class, DlocalBillingGateway::class);
    }

    /**
     * Bootstrap any package services.
     * 
     * Handles publishing of config and migrations, and loads package routes.
     * 
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/Core/Config/dlocal.php' => config_path('dlocal.php'),
            ], 'dlocal-config');

            // Publish migrations using the recommended method
            $this->publishesMigrations([
                __DIR__.'/Database/migrations' => database_path('migrations'),
            ], 'dlocal-migrations');
        }

        // Load routes when available
        $this->loadRoutesFrom(__DIR__.'/Routes/api.php');
    }
}

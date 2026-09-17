<?php

namespace NumbersNebula\OmanPayments\Providers;

use Illuminate\Support\ServiceProvider;

class OmanPaymentsServiceProvider extends ServiceProvider
{
    /**
     * Register package services and configurations.
     */
    public function register(): void
    {
        $this->registerConfig();
    }

    /**
     * Bootstrap package services, routes, views, translations, and migrations.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'oman_payments');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'oman_payments');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->publishes([
            dirname(__DIR__).'/Config/payment-methods.php' => config_path('payment_methods_oman.php'),
        ], 'oman-payments-config');
    }

    /**
     * Merge payment methods and system configuration into application config.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/payment-methods.php',
            'payment_methods'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/system.php',
            'core'
        );
    }
}

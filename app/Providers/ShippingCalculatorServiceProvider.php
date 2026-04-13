<?php

namespace App\Providers;

use App\Services\CurrencyService;
use App\Services\ShippingCalculatorService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;


class ShippingCalculatorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ShippingCalculatorService::class, function (Application $app) {
            return new ShippingCalculatorService($app(CurrencyService::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

<?php

namespace App\Providers;

use App\Services\CurrencyService;
use App\Services\SettingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;



class CurrencyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrencyService::class, function () {
            $allowed = true;
            $origin = request()->header('origin');

            if ($origin === env('BACKOFFICE_FE_URL'))
                $allowed = false;

            if (!app(SettingService::class)->get('currency_enabled', true)) {
                $allowed = false;
            }

            return new CurrencyService(
                $allowed,
                app(SettingService::class)
            );
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

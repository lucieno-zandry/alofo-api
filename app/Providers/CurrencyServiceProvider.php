<?php

namespace App\Providers;

use App\Services\CurrencyService;
use Illuminate\Support\ServiceProvider;

use function Illuminate\Log\log;

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

            return new CurrencyService($allowed);
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

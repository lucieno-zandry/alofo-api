<?php

namespace App\Providers;

use App\Services\PreferenceService;
use Illuminate\Support\ServiceProvider;

class PreferenceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PreferenceService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

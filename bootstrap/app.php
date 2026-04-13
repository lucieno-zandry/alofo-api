<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append([HandleCors::class]);
        $middleware->alias([
            'custom.sanctum' => \App\Http\Middleware\CustomSanctumAuth::class,
            'verified'       => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'approved'       => \App\Http\Middleware\EnsureUserIsApproved::class,
            'not-blocked'    => \App\Http\Middleware\EnsureUserIsNotBlocked::class,
            'not-suspended'  => \App\Http\Middleware\EnsureUserIsNotSuspended::class,
            'not-in-maintenance-mode' => \App\Http\Middleware\EnsureAppIsNotInMaintenanceMode::class
        ]);

        $middleware->group('api.auth', [
            'custom.sanctum',
            'not-in-maintenance-mode',
            'verified',
            'not-blocked',
            'not-suspended',
        ]);

        $middleware->group('api.auth.approved', [
            'custom.sanctum',
            'not-in-maintenance-mode',
            'verified',
            'not-blocked',
            'not-suspended',
            'approved',
        ]);
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SetLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

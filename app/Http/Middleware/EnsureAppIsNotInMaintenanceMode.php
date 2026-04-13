<?php

namespace App\Http\Middleware;

use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function Illuminate\Log\log;

class EnsureAppIsNotInMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User */
        $user = auth('sanctum')->user();

        log("Middleware executed");

        if (!$user->roleIsAdmin()) {
            if (app(SettingService::class)->get('maintenance_mode', false)) {
                return response()->json(['message' => 'App is in maintenance mode'], 403);
            }
        }

        return $next($request);
    }
}

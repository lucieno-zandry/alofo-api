<?php

namespace App\Http\Middleware;

use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppIsNotInMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (app(SettingService::class)->get('maintenance_mode', false)) {
            /** @var \App\Models\User */
            $user = auth('sanctum')->user();
            
            if ($user && !$user->roleIsAdmin()) {
                return response()->json(['message' => 'App is in maintenance mode', 'action' => 'MAINTENANCE_MODE_ON'], 403);
            }
        }

        return $next($request);
    }
}

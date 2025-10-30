<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CustomSanctumAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('sanctum')->check()) {
            return response()->json([
                'message' => 'Forbidden',
                'status' => 403,
                'action' => 'AUTHENTICATE'
            ], 403);
        }

        return $next($request);
    }
}

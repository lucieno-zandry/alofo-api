<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CustomSanctumAuth
{
    public function handle(Request $request, Closure $next)
    {
        Auth::shouldUse('sanctum');

        try {
            /** @var \App\Models\User */
            $user = auth('sanctum')->user();
            if (!$user || $user->roleIsGuest()) throw new Exception("Authentication required!");
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage() ?? 'Forbidden',
                'status'  => 403,
                'action'  => 'AUTHENTICATE'
            ], 403);
        }

        return $next($request);
    }
}

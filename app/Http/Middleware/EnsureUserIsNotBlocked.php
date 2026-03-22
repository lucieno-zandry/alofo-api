<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum');

        if ($user?->status?->status === "blocked") {
            return response()->json([
                'message' => 'Your account is blocked!',
                'action' => 'ACCOUNT_BLOCKED'
            ], 403);
        }

        return $next($request);
    }
}

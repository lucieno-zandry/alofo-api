<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user())
            abort(403);

        if ($request->user()->roleIsAdmin() && !$request->user()->hasBeenApproved())
            return response()->json([
                'message' => 'Account still not approved',
                'redirect_url' => env('USER_APPROBATION_URL'),
                'action' => 'APPROVE_ACCOUNT'
            ], 403);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Helpers\Functions;
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

        if ($request->user()->roleIsAdmin() && !$request->user()->hasBeenApproved()) {
            $user_approbation_url = Functions::get_frontend_url('USER_APPROBATION_PATHNAME');

            return response()->json([
                'message' => 'Account still not approved',
                'redirect_url' => $user_approbation_url,
                'action' => 'APPROVE_ACCOUNT'
            ], 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Helpers\Functions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->hasVerifiedEmail()) {
            $redirect_url = Functions::get_frontend_url('email_verify_pathname', $request->user()->role);

            return response()->json([
                'message' => 'Email verification required.',
                'redirect_url' => $redirect_url,
                'action' => 'VERIFY_EMAIL'
            ], 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    { // Check for 'Accept-Language' header, fallback to app config default
        $locale = $request->header('Accept-Language', config('app.locale'));

        // Validate if the locale is supported (optional but recommended)
        if (in_array($locale, ['en', 'fr'])) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}

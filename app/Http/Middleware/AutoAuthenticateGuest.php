<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AutoAuthenticateGuest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth('sanctum')->check()) {
            return $next($request);
        }

        $name = 'guest_' . Str::uuid();

        // Create a clean guest user
        $guest = User::create([
            'name' => 'guest',
            'email' =>  $name . '@guest.local',
            'role' => 'guest',
        ]);

        $token = $guest->createToken('guest')->plainTextToken;

        // Authenticate the request
        $request->headers->set('Authorization', 'Bearer ' . $token);
        auth('sanctum')->setUser($guest);

        $response = $next($request);

        $response
            ->cookie('auth_token', $token, 60 * 27 * 30)
            ->cookie('guest_token', $token, 60 * 27 * 30);

        return $response;
    }
}

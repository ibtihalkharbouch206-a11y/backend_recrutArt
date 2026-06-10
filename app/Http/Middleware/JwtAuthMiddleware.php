<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\JwtService;
use App\Models\User;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = substr($header, 7);
        $payload = JwtService::decode($token);

        if (!$payload || !isset($payload['sub'])) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = User::find($payload['sub']);

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Set the user in Auth guard and Request
        Auth::setUser($user);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}

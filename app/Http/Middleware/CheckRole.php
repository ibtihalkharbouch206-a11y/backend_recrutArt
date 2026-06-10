<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Allow admin to bypass role checks
        if (strtolower((string)$user->role) === 'admin') {
            return $next($request);
        }

        if (strtolower((string)$user->role) !== strtolower($role)) {
            return response()->json(['message' => 'Forbidden - insufficient role'], 403);
        }

        return $next($request);
    }
}

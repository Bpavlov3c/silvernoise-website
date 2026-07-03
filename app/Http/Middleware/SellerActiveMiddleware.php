<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SellerActiveMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'seller') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Your account is not active.'], 403);
        }

        if ($user->is_blocked) {
            return response()->json(['message' => 'Your account has been suspended.'], 403);
        }

        return $next($request);
    }
}

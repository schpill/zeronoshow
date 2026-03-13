<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LeoAddonActiveMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $business = $request->user();

        if (! $business?->leo_addon_active) {
            return response()->json([
                'message' => 'L’option Léo doit être activée pour gérer un canal.',
            ], 402);
        }

        return $next($request);
    }
}

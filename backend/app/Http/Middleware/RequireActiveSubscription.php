<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $business = $request->user();

        if (
            $business
            && ! $business->isOnActivePlan()
            && ! $request->isMethod('get')
            && ! $request->isMethod('head')
        ) {
            return response()->json([
                'error' => [
                    'code' => 'SUBSCRIPTION_REQUIRED',
                    'message' => "Votre période d'essai est terminée. Abonnez-vous pour continuer.",
                ],
            ], 402);
        }

        return $next($request);
    }
}

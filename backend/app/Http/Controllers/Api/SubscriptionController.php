<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function checkout(Request $request): JsonResponse
    {
        $session = app(StripeService::class)->createCheckoutSession($request->user());

        return response()->json([
            'checkout_url' => $session['url'],
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $business = $request->user();

        return response()->json([
            'subscription_status' => $business->subscription_status,
            'trial_ends_at' => $business->trial_ends_at?->toIso8601String(),
            'stripe_customer_id' => $business->stripe_customer_id,
            'sms_cost_this_month' => round((float) SmsLog::query()
                ->where('business_id', $business->id)
                ->where('status', '!=', 'failed')
                ->whereBetween('created_at', [now()->startOfMonth()->utc(), now()->endOfMonth()->utc()])
                ->sum('cost_eur'), 3),
        ]);
    }
}

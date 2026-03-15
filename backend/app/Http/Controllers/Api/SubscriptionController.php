<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Subscription', description: 'Subscription and billing endpoints')]
class SubscriptionController extends Controller
{
    #[OA\Post(
        path: '/api/v1/subscription/checkout',
        tags: ['Subscription'],
        summary: 'Create subscription checkout session',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Checkout session created')],
    )]
    public function checkout(Request $request): JsonResponse
    {
        $business = $request->user();
        $session = app(StripeService::class)->createCheckoutSession($business);

        if (($session['customer_id'] ?? null) !== null && $business->stripe_customer_id !== $session['customer_id']) {
            $business->forceFill([
                'stripe_customer_id' => $session['customer_id'],
            ])->save();
        }

        return response()->json([
            'checkout_url' => $session['url'],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/subscription',
        tags: ['Subscription'],
        summary: 'Get current subscription state',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Subscription state')],
    )]
    public function show(Request $request): JsonResponse
    {
        $business = $request->user();

        return response()->json([
            'subscription_status' => $business->subscription_status,
            'trial_ends_at' => $business->trial_ends_at?->toIso8601String(),
            'stripe_customer_id' => $business->stripe_customer_id,
            'leo_addon_active' => (bool) $business->leo_addon_active,
            'leo_addon_stripe_item_id' => $business->leo_addon_stripe_item_id,
            'sms_cost_this_month' => round((float) SmsLog::query()
                ->where('business_id', $business->id)
                ->where('status', '!=', 'failed')
                ->whereBetween('created_at', [now()->startOfMonth()->utc(), now()->endOfMonth()->utc()])
                ->sum('cost_eur'), 3),
        ]);
    }
}

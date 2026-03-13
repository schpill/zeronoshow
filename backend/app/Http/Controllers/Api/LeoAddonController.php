<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeoChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\StripeClient;

class LeoAddonController extends Controller
{
    public function activate(Request $request): JsonResponse
    {
        $business = $request->user();

        if ($business->leo_addon_active && $business->leo_addon_stripe_item_id) {
            return response()->json([
                'activated' => true,
                'checkout_url' => null,
            ]);
        }

        if (! $business->stripe_subscription_id) {
            return response()->json([
                'message' => 'Aucun abonnement Stripe actif n’a ete trouve.',
            ], 402);
        }

        $client = new StripeClient((string) config('services.stripe.secret'));
        $item = $client->subscriptionItems->create([
            'subscription' => $business->stripe_subscription_id,
            'price' => (string) config('leo.stripe.price_id'),
            'proration_behavior' => 'create_prorations',
        ]);

        $business->forceFill([
            'leo_addon_active' => true,
            'leo_addon_stripe_item_id' => (string) $item->id,
        ])->save();

        return response()->json([
            'activated' => true,
            'checkout_url' => null,
        ]);
    }

    public function deactivate(Request $request): JsonResponse
    {
        $business = $request->user();

        if ($business->leo_addon_stripe_item_id) {
            $client = new StripeClient((string) config('services.stripe.secret'));
            $client->subscriptionItems->delete($business->leo_addon_stripe_item_id, []);
        }

        $business->forceFill([
            'leo_addon_active' => false,
            'leo_addon_stripe_item_id' => null,
        ])->save();

        LeoChannel::query()
            ->where('business_id', $business->id)
            ->update(['is_active' => false]);

        return response()->json(null, 204);
    }

    public function status(Request $request): JsonResponse
    {
        $business = $request->user();

        return response()->json([
            'active' => (bool) $business->leo_addon_active,
            'stripe_item_id' => $business->leo_addon_stripe_item_id,
        ]);
    }
}

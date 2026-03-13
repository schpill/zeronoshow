<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature');
        $secret = (string) config('services.stripe.webhook_secret');

        if (! $this->hasValidSignature($payload, $signature, $secret)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 400);
        }

        /** @var array{type?: string, data?: array{object?: array<string, mixed>}} $event */
        $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $object = $event['data']['object'] ?? [];

        match ($event['type'] ?? null) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($object),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($object),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleCheckoutCompleted(array $payload): void
    {
        $business = Business::query()
            ->where('stripe_customer_id', $payload['customer'] ?? null)
            ->first();

        if (! $business) {
            return;
        }

        $business->forceFill([
            'subscription_status' => 'active',
            'stripe_customer_id' => $payload['customer'] ?? $business->stripe_customer_id,
            'stripe_subscription_id' => $payload['subscription'] ?? $business->stripe_subscription_id,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleSubscriptionDeleted(array $payload): void
    {
        Business::query()
            ->where('stripe_customer_id', $payload['customer'] ?? null)
            ->orWhere('stripe_subscription_id', $payload['id'] ?? null)
            ->update([
                'subscription_status' => 'cancelled',
            ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleInvoicePaymentFailed(array $payload): void
    {
        Business::query()
            ->where('stripe_customer_id', $payload['customer'] ?? null)
            ->update([
                'subscription_status' => 'past_due',
            ]);
    }

    private function hasValidSignature(string $payload, string $header, string $secret): bool
    {
        if ($payload === '' || $header === '' || $secret === '') {
            return false;
        }

        $parts = collect(explode(',', $header))
            ->mapWithKeys(function (string $part): array {
                [$key, $value] = array_pad(explode('=', $part, 2), 2, null);

                return [$key => $value];
            });

        $timestamp = $parts->get('t');
        $signature = $parts->get('v1');

        if (! is_string($timestamp) || ! is_string($signature)) {
            return false;
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return hash_equals($expected, $signature);
    }
}

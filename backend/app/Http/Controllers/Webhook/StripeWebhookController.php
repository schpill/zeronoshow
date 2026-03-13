<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Mail\PaymentFailedStub;
use App\Models\Business;
use App\Models\LeoChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature');
        $secret = (string) config('services.stripe.webhook_secret');

        try {
            Webhook::constructEvent($payload, $signature, $secret, PHP_INT_MAX);
        } catch (UnexpectedValueException|SignatureVerificationException) {
            return response()->json(['message' => 'Invalid webhook signature.'], 400);
        }

        /** @var array{type?: string, data?: array{object?: array<string, mixed>}} $event */
        $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $object = $event['data']['object'] ?? [];

        match ($event['type'] ?? null) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($object),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($object),
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
            ->when(
                isset($payload['client_reference_id']),
                fn ($query) => $query->where('id', $payload['client_reference_id'])
            )
            ->when(
                ! isset($payload['client_reference_id']) && isset($payload['customer_email']),
                fn ($query) => $query->where('email', $payload['customer_email'])
            )
            ->when(
                ! isset($payload['client_reference_id']) && ! isset($payload['customer_email']) && isset($payload['customer']),
                fn ($query) => $query->where('stripe_customer_id', $payload['customer'])
            )
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
                'leo_addon_active' => false,
                'leo_addon_stripe_item_id' => null,
            ]);

        $this->deactivateLeoChannels($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleSubscriptionUpdated(array $payload): void
    {
        $business = Business::query()
            ->where('stripe_customer_id', $payload['customer'] ?? null)
            ->orWhere('stripe_subscription_id', $payload['id'] ?? null)
            ->first();

        if (! $business) {
            return;
        }

        $leoPriceId = (string) config('leo.stripe.price_id');
        $items = data_get($payload, 'items.data', []);
        $leoItem = collect(is_array($items) ? $items : [])
            ->first(fn (mixed $item): bool => data_get($item, 'price.id') === $leoPriceId);

        if ($leoItem) {
            $business->forceFill([
                'leo_addon_active' => true,
                'leo_addon_stripe_item_id' => (string) data_get($leoItem, 'id'),
            ])->save();

            return;
        }

        $business->forceFill([
            'leo_addon_active' => false,
            'leo_addon_stripe_item_id' => null,
        ])->save();

        LeoChannel::query()
            ->where('business_id', $business->id)
            ->update(['is_active' => false]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleInvoicePaymentFailed(array $payload): void
    {
        $business = Business::query()
            ->where('stripe_customer_id', $payload['customer'] ?? null)
            ->first();

        Log::warning('Stripe invoice payment failed.', [
            'customer' => $payload['customer'] ?? null,
            'invoice_id' => $payload['id'] ?? null,
        ]);

        if ($business) {
            Mail::to($business->email)->queue(
                new PaymentFailedStub($business, (string) ($payload['id'] ?? 'unknown'))
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function deactivateLeoChannels(array $payload): void
    {
        $business = Business::query()
            ->where('stripe_customer_id', $payload['customer'] ?? null)
            ->orWhere('stripe_subscription_id', $payload['id'] ?? null)
            ->first();

        if (! $business) {
            return;
        }

        LeoChannel::query()
            ->where('business_id', $business->id)
            ->update(['is_active' => false]);
    }
}

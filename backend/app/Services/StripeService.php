<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripeService
{
    public function createCheckoutSession(Business $business): array
    {
        $response = Http::asForm()
            ->withBasicAuth((string) config('services.stripe.secret'), '')
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'subscription',
                'success_url' => rtrim((string) config('app.frontend_url', config('app.url')), '/').'/subscription?status=success',
                'cancel_url' => rtrim((string) config('app.frontend_url', config('app.url')), '/').'/subscription?status=cancelled',
                'line_items[0][price_data][currency]' => 'eur',
                'line_items[0][price_data][product_data][name]' => 'ZeroNoShow Monthly Subscription',
                'line_items[0][price_data][recurring][interval]' => 'month',
                'line_items[0][price_data][unit_amount]' => 1900,
                'line_items[0][quantity]' => 1,
                'customer_email' => $business->email,
                'client_reference_id' => $business->id,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to create Stripe Checkout session.');
        }

        /** @var array{id: string, url: string, customer?: string} $payload */
        $payload = $response->json();

        if (($payload['customer'] ?? null) !== null) {
            $business->forceFill([
                'stripe_customer_id' => $payload['customer'],
            ])->save();
        }

        return [
            'id' => $payload['id'],
            'url' => $payload['url'],
            'customer_id' => $payload['customer'] ?? $business->stripe_customer_id,
        ];
    }

    public function createInvoiceItem(Business $business, int $amountInCents, string $period): void
    {
        if ($business->stripe_customer_id === null) {
            return;
        }

        $cacheKey = sprintf('stripe:invoice-item:sms:%s:%s', $business->id, $period);

        if (Cache::has($cacheKey)) {
            return;
        }

        $response = Http::asForm()
            ->withBasicAuth((string) config('services.stripe.secret'), '')
            ->post('https://api.stripe.com/v1/invoiceitems', [
                'customer' => $business->stripe_customer_id,
                'amount' => $amountInCents,
                'currency' => 'eur',
                'description' => sprintf('SMS ZeroNoShow - %s', $period),
                'metadata[business_id]' => $business->id,
                'metadata[period]' => $period,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to create Stripe invoice item.');
        }

        Cache::forever($cacheKey, true);
    }
}

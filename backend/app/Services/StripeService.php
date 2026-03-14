<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Facades\Cache;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\InvoiceItem;
use Stripe\StripeClient;

class StripeService
{
    public function leoAddonPriceId(): string
    {
        return (string) (config('leo.stripe.price_id') ?: Cache::get('leo:stripe:price_id', ''));
    }

    /**
     * @return array{id: string, url: string, customer_id: string|null}
     *
     * @throws ApiErrorException
     */
    public function createCheckoutSession(Business $business): array
    {
        $client = new StripeClient((string) config('services.stripe.secret'));
        $customerId = $this->resolveCustomerId($client, $business);
        /** @var Session $session */
        $session = $client->checkout->sessions->create([
            'mode' => 'subscription',
            'customer' => $customerId,
            'client_reference_id' => $business->id,
            'customer_email' => $business->email,
            'success_url' => rtrim((string) config('app.frontend_url', config('app.url')), '/').'/subscription?status=success',
            'cancel_url' => rtrim((string) config('app.frontend_url', config('app.url')), '/').'/subscription?status=cancelled',
            'line_items' => [[
                'price' => (string) config('services.stripe.price_id'),
                'quantity' => 1,
            ]],
        ]);

        return [
            'id' => (string) $session->id,
            'url' => (string) $session->url,
            'customer_id' => $customerId,
        ];
    }

    /**
     * @return array{id: string, url: string}
     *
     * @throws ApiErrorException
     */
    public function createWhatsAppCreditCheckoutSession(Business $business, int $amountCents): array
    {
        $client = new StripeClient((string) config('services.stripe.secret'));
        $customerId = $this->resolveCustomerId($client, $business);

        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        /** @var Session $session */
        $session = $client->checkout->sessions->create([
            'mode' => 'payment',
            'customer' => $customerId,
            'client_reference_id' => $business->id,
            'success_url' => $frontendUrl.'/leo/whatsapp/topup/return?status=success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $frontendUrl.'/leo/whatsapp/topup/return?status=cancel',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Léo WhatsApp — Crédit prépayé',
                    ],
                    'unit_amount' => $amountCents,
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'type' => 'whatsapp_credit',
                'business_id' => $business->id,
            ],
        ]);

        return [
            'id' => (string) $session->id,
            'url' => (string) $session->url,
        ];
    }

    /**
     * @throws ApiErrorException
     */
    public function createInvoiceItem(Business $business, int $amountInCents, string $period): void
    {
        if ($business->stripe_customer_id === null) {
            return;
        }

        $cacheKey = sprintf('stripe:invoice-item:sms:%s:%s', $business->id, $period);

        if (Cache::has($cacheKey)) {
            return;
        }

        $client = new StripeClient((string) config('services.stripe.secret'));
        /** @var InvoiceItem $invoiceItem */
        $invoiceItem = $client->invoiceItems->create([
            'customer' => $business->stripe_customer_id,
            'amount' => $amountInCents,
            'currency' => 'eur',
            'description' => sprintf('SMS ZeroNoShow - %s', $period),
            'metadata' => [
                'business_id' => $business->id,
                'period' => $period,
            ],
        ]);

        Cache::forever($cacheKey, (string) $invoiceItem->id);
    }

    /**
     * @throws ApiErrorException
     */
    public function createWhatsAppInvoiceItem(Business $business, int $amountCents): void
    {
        if ($business->stripe_customer_id === null) {
            return;
        }

        $client = new StripeClient((string) config('services.stripe.secret'));
        $client->invoiceItems->create([
            'customer' => $business->stripe_customer_id,
            'amount' => $amountCents,
            'currency' => 'eur',
            'description' => 'Léo WhatsApp — Crédit mensuel',
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function finalizeAndPayInvoice(Business $business): void
    {
        if ($business->stripe_customer_id === null) {
            return;
        }

        $client = new StripeClient((string) config('services.stripe.secret'));
        $invoice = $client->invoices->create([
            'customer' => $business->stripe_customer_id,
            'auto_advance' => true,
        ]);

        $invoice->finalizeInvoice();
        $invoice->pay();
    }

    /**
     * @return array{id: string}
     *
     * @throws ApiErrorException
     */
    public function createSubscriptionItem(string $subscriptionId, string $priceId): array
    {
        $client = new StripeClient((string) config('services.stripe.secret'));
        $item = $client->subscriptionItems->create([
            'subscription' => $subscriptionId,
            'price' => $priceId,
            'proration_behavior' => 'create_prorations',
        ]);

        return [
            'id' => (string) $item->id,
        ];
    }

    /**
     * @throws ApiErrorException
     */
    public function deleteSubscriptionItem(string $subscriptionItemId): void
    {
        $client = new StripeClient((string) config('services.stripe.secret'));
        $client->subscriptionItems->delete($subscriptionItemId, []);
    }

    /**
     * @throws ApiErrorException
     */
    private function resolveCustomerId(StripeClient $client, Business $business): string
    {
        if ($business->stripe_customer_id !== null) {
            return $business->stripe_customer_id;
        }

        /** @var Customer $customer */
        $customer = $client->customers->create([
            'email' => $business->email,
            'name' => $business->name,
            'phone' => $business->phone,
            'metadata' => [
                'business_id' => $business->id,
            ],
        ]);

        $business->forceFill([
            'stripe_customer_id' => (string) $customer->id,
        ])->save();

        return (string) $customer->id;
    }
}

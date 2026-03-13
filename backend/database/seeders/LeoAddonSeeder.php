<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Stripe\StripeClient;

class LeoAddonSeeder extends Seeder
{
    public function run(): void
    {
        $configuredPriceId = (string) config('leo.stripe.price_id');

        if ($configuredPriceId !== '') {
            Cache::forever('leo:stripe:price_id', $configuredPriceId);

            return;
        }

        $secret = (string) config('services.stripe.secret');

        if ($secret === '') {
            return;
        }

        $client = new StripeClient($secret);
        $products = $client->products->all(['limit' => 100]);

        /** @var object|null $product */
        $product = collect($products->data)->first(
            fn (object $item): bool => (($item->metadata['scope'] ?? null) === 'leo-addon')
        );

        if (! $product) {
            $product = $client->products->create([
                'name' => 'Léo Assistant',
                'metadata' => [
                    'scope' => 'leo-addon',
                ],
            ]);
        }

        $prices = $client->prices->all([
            'product' => $product->id,
            'limit' => 100,
        ]);

        /** @var object|null $price */
        $price = collect($prices->data)->first(function (object $item): bool {
            return $item->currency === 'eur'
                && (int) $item->unit_amount === 900
                && (($item->recurring->interval ?? null) === 'month');
        });

        if (! $price) {
            $price = $client->prices->create([
                'product' => $product->id,
                'currency' => 'eur',
                'unit_amount' => 900,
                'recurring' => [
                    'interval' => 'month',
                ],
            ]);
        }

        Cache::forever('leo:stripe:price_id', $price->id);
    }
}

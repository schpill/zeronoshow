<?php

namespace Tests\Feature\Leo;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class StripeWebhookLeoAddonTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_activates_leo_addon_state_from_subscription_updated_webhook(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');
        config()->set('leo.stripe.price_id', 'price_leo');

        $business = Business::factory()->create([
            'stripe_customer_id' => 'cus_leo',
            'stripe_subscription_id' => 'sub_leo',
            'leo_addon_active' => false,
        ]);

        $payload = json_encode([
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_leo',
                    'customer' => 'cus_leo',
                    'items' => [
                        'data' => [
                            [
                                'id' => 'si_leo',
                                'price' => ['id' => 'price_leo'],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->callWebhook($payload)->assertOk();

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'leo_addon_active' => true,
            'leo_addon_stripe_item_id' => 'si_leo',
        ]);
    }

    public function test_it_deactivates_leo_channels_when_addon_disappears_from_subscription(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');
        config()->set('leo.stripe.price_id', 'price_leo');

        $business = Business::factory()->create([
            'stripe_customer_id' => 'cus_leo',
            'stripe_subscription_id' => 'sub_leo',
            'leo_addon_active' => true,
            'leo_addon_stripe_item_id' => 'si_leo',
        ]);

        DB::table('leo_channels')->insert([
            'id' => (string) Str::uuid(),
            'business_id' => $business->id,
            'channel' => 'telegram',
            'external_identifier' => '123456789',
            'bot_name' => 'Léo',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = json_encode([
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_leo',
                    'customer' => 'cus_leo',
                    'items' => [
                        'data' => [],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->callWebhook($payload)->assertOk();

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'leo_addon_active' => false,
            'leo_addon_stripe_item_id' => null,
        ]);

        $this->assertDatabaseHas('leo_channels', [
            'business_id' => $business->id,
            'is_active' => false,
        ]);
    }

    public function test_it_deactivates_leo_addon_state_when_subscription_is_deleted(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');

        $business = Business::factory()->create([
            'stripe_customer_id' => 'cus_leo',
            'stripe_subscription_id' => 'sub_leo',
            'leo_addon_active' => true,
            'leo_addon_stripe_item_id' => 'si_leo',
        ]);

        DB::table('leo_channels')->insert([
            'id' => (string) Str::uuid(),
            'business_id' => $business->id,
            'channel' => 'telegram',
            'external_identifier' => '123456789',
            'bot_name' => 'Léo',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = json_encode([
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_leo',
                    'customer' => 'cus_leo',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->callWebhook($payload)->assertOk();

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'leo_addon_active' => false,
            'leo_addon_stripe_item_id' => null,
            'subscription_status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('leo_channels', [
            'business_id' => $business->id,
            'is_active' => false,
        ]);
    }

    public function test_it_uses_the_cached_leo_price_id_when_the_config_value_is_empty(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');
        config()->set('leo.stripe.price_id', null);
        Cache::forever('leo:stripe:price_id', 'price_cached_leo');

        $business = Business::factory()->create([
            'stripe_customer_id' => 'cus_leo',
            'stripe_subscription_id' => 'sub_leo',
            'leo_addon_active' => false,
        ]);

        $payload = json_encode([
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_leo',
                    'customer' => 'cus_leo',
                    'items' => [
                        'data' => [
                            [
                                'id' => 'si_leo_cached',
                                'price' => ['id' => 'price_cached_leo'],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->callWebhook($payload)->assertOk();

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'leo_addon_active' => true,
            'leo_addon_stripe_item_id' => 'si_leo_cached',
        ]);
    }

    private function callWebhook(string $payload)
    {
        return $this->call(
            'POST',
            '/api/v1/webhooks/stripe',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $this->stripeSignature($payload, 'whsec_test'),
            ],
            $payload,
        );
    }

    private function stripeSignature(string $payload, string $secret): string
    {
        $timestamp = 1234567890;
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return "t={$timestamp},v1={$signature}";
    }
}

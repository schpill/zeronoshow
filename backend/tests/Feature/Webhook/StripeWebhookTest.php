<?php

namespace Tests\Feature\Webhook;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_a_webhook_with_an_invalid_signature(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');

        $response = $this->postJson('/api/v1/webhooks/stripe', [
            'type' => 'checkout.session.completed',
        ], [
            'Stripe-Signature' => 't=123,v1=invalid',
        ]);

        $response->assertStatus(400);
    }

    public function test_it_activates_the_subscription_when_checkout_completes(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');

        $business = Business::factory()->create([
            'subscription_status' => 'trial',
            'stripe_customer_id' => 'cus_test_123',
        ]);

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'customer' => 'cus_test_123',
                    'subscription' => 'sub_test_123',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = $this->stripeSignature($payload, 'whsec_test');

        $response = $this->call(
            'POST',
            '/api/v1/webhooks/stripe',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload,
        );

        $response->assertOk();

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'subscription_status' => 'active',
            'stripe_subscription_id' => 'sub_test_123',
        ]);
    }

    private function stripeSignature(string $payload, string $secret): string
    {
        $timestamp = 1234567890;
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return "t={$timestamp},v1={$signature}";
    }
}

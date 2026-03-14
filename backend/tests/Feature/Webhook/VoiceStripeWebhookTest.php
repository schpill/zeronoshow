<?php

namespace Tests\Feature\Webhook;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceStripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_session_completed_with_voice_credit_metadata_tops_up_balance(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');

        $business = Business::factory()->create([
            'voice_credit_cents' => 300,
        ]);

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'client_reference_id' => $business->id,
                    'amount_total' => 800,
                    'metadata' => [
                        'type' => 'voice_credit',
                        'business_id' => $business->id,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->callWebhook($payload)->assertOk();

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'voice_credit_cents' => 1100,
        ]);
    }

    public function test_checkout_session_completed_with_non_voice_metadata_is_ignored_for_voice_balance(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');

        $business = Business::factory()->create([
            'voice_credit_cents' => 300,
            'subscription_status' => 'trial',
        ]);

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'client_reference_id' => $business->id,
                    'customer' => 'cus_test_123',
                    'subscription' => 'sub_test_123',
                    'metadata' => [
                        'type' => 'subscription',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->callWebhook($payload)->assertOk();

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'voice_credit_cents' => 300,
            'subscription_status' => 'active',
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

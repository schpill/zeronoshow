<?php

namespace Tests\Feature\Voice;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceStripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_completed_with_voice_credit_tops_up_balance(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $business = Business::factory()->create([
            'voice_credit_cents' => 100,
        ]);

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'client_reference_id' => $business->id,
                    'amount_total' => 1200,
                    'metadata' => [
                        'type' => 'voice_credit',
                        'business_id' => $business->id,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->call(
            'POST',
            '/api/v1/webhooks/stripe',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $this->stripeSignature($payload),
            ],
            $payload,
        );

        $response->assertOk();
        $this->assertSame(1300, $business->fresh()->voice_credit_cents);
    }

    public function test_non_voice_metadata_does_not_top_up_balance(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $business = Business::factory()->create([
            'voice_credit_cents' => 100,
        ]);

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'client_reference_id' => $business->id,
                    'amount_total' => 1200,
                    'metadata' => [
                        'type' => 'other',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->call(
            'POST',
            '/api/v1/webhooks/stripe',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $this->stripeSignature($payload),
            ],
            $payload,
        );

        $response->assertOk();
        $this->assertSame(100, $business->fresh()->voice_credit_cents);
    }

    private function stripeSignature(string $payload): string
    {
        $timestamp = 1234567890;
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, 'whsec_test');

        return "t={$timestamp},v1={$signature}";
    }
}

<?php

namespace Tests\Feature\Webhook;

use App\Mail\PaymentFailedStub;
use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
            'stripe_customer_id' => null,
        ]);

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'customer' => 'cus_test_123',
                    'customer_email' => $business->email,
                    'client_reference_id' => $business->id,
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

    public function test_it_activates_the_subscription_by_matching_customer_email_when_reference_and_customer_id_are_missing(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');

        $business = Business::factory()->create([
            'subscription_status' => 'trial',
            'stripe_customer_id' => null,
        ]);

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'customer' => 'cus_test_fallback',
                    'customer_email' => $business->email,
                    'subscription' => 'sub_test_fallback',
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
                'HTTP_STRIPE_SIGNATURE' => $this->stripeSignature($payload, 'whsec_test'),
            ],
            $payload,
        );

        $response->assertOk();

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'subscription_status' => 'active',
            'stripe_customer_id' => 'cus_test_fallback',
            'stripe_subscription_id' => 'sub_test_fallback',
        ]);
    }

    public function test_it_marks_the_subscription_as_cancelled_when_subscription_is_deleted(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');

        $business = Business::factory()->create([
            'subscription_status' => 'active',
            'stripe_customer_id' => 'cus_test_123',
            'stripe_subscription_id' => 'sub_test_123',
        ]);

        $payload = json_encode([
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'customer' => 'cus_test_123',
                    'id' => 'sub_test_123',
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
                'HTTP_STRIPE_SIGNATURE' => $this->stripeSignature($payload, 'whsec_test'),
            ],
            $payload,
        );

        $response->assertOk();

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'subscription_status' => 'cancelled',
        ]);
    }

    public function test_it_logs_a_warning_and_queues_a_stub_mail_when_payment_fails(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');
        Log::spy();
        Mail::fake();

        $business = Business::factory()->create([
            'subscription_status' => 'active',
            'stripe_customer_id' => 'cus_test_123',
        ]);

        $payload = json_encode([
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'customer' => 'cus_test_123',
                    'id' => 'in_test_123',
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
                'HTTP_STRIPE_SIGNATURE' => $this->stripeSignature($payload, 'whsec_test'),
            ],
            $payload,
        );

        $response->assertOk();

        Log::shouldHaveReceived('warning')->once();
        Mail::assertQueued(PaymentFailedStub::class, fn (PaymentFailedStub $mail) => $mail->hasTo($business->email));
    }

    private function stripeSignature(string $payload, string $secret): string
    {
        $timestamp = 1234567890;
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return "t={$timestamp},v1={$signature}";
    }
}

<?php

namespace Tests\Feature\Subscription;

use App\Models\Business;
use App\Models\Reservation;
use App\Models\SmsLog;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_subscription_snapshot(): void
    {
        $business = Business::factory()->create([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(3),
        ]);
        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
        ]);
        SmsLog::factory()->create([
            'business_id' => $business->id,
            'reservation_id' => $reservation->id,
            'cost_eur' => 0.123,
            'created_at' => now()->startOfMonth()->addDay(),
        ]);

        Sanctum::actingAs($business);

        $response = $this->getJson('/api/v1/subscription');

        $response
            ->assertOk()
            ->assertJsonPath('subscription_status', 'trial')
            ->assertJsonPath('sms_cost_this_month', 0.123)
            ->assertJsonPath('trial_ends_at', $business->trial_ends_at?->toIso8601String());
    }

    public function test_it_creates_a_checkout_session_and_returns_the_redirect_url(): void
    {
        $business = Business::factory()->create([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->instance(StripeService::class, new class
        {
            public function createCheckoutSession(Business $business): array
            {
                return [
                    'id' => 'cs_test_123',
                    'url' => 'https://checkout.stripe.test/session/cs_test_123',
                    'customer_id' => 'cus_test_123',
                ];
            }
        });

        Sanctum::actingAs($business);

        $response = $this->postJson('/api/v1/subscription/checkout');

        $response
            ->assertOk()
            ->assertJsonPath('checkout_url', 'https://checkout.stripe.test/session/cs_test_123');
    }
}

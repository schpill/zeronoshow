<?php

namespace Tests\Feature\Subscription;

use App\Models\Business;
use App\Models\SmsLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_subscription_status_trial_end_and_sms_cost_this_month(): void
    {
        $business = Business::factory()->create([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(5),
            'stripe_customer_id' => 'cus_123',
        ]);

        Sanctum::actingAs($business);

        SmsLog::factory()->create([
            'business_id' => $business->id,
            'status' => 'delivered',
            'cost_eur' => 0.19,
            'created_at' => now()->startOfMonth()->addDay(),
        ]);

        SmsLog::factory()->create([
            'business_id' => $business->id,
            'status' => 'failed',
            'cost_eur' => 0.99,
            'created_at' => now()->startOfMonth()->addDays(2),
        ]);

        $this->getJson('/api/v1/subscription')
            ->assertOk()
            ->assertJsonPath('subscription_status', 'trial')
            ->assertJsonPath('stripe_customer_id', 'cus_123')
            ->assertJsonPath('sms_cost_this_month', 0.19)
            ->assertJsonPath('trial_ends_at', $business->trial_ends_at?->toIso8601String());
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson('/api/v1/subscription')->assertUnauthorized();
    }
}

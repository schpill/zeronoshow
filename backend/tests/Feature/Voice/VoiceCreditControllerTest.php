<?php

namespace Tests\Feature\Voice;

use App\Models\Business;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceCreditControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_returns_credit_and_settings(): void
    {
        $business = Business::factory()->create([
            'voice_credit_cents' => 500,
            'voice_monthly_cap_cents' => 1000,
            'voice_auto_renew' => true,
            'voice_auto_call_enabled' => true,
            'voice_auto_call_score_threshold' => 35,
            'voice_auto_call_min_party_size' => 6,
            'voice_retry_count' => 3,
            'voice_retry_delay_minutes' => 10,
        ]);

        $response = $this->actingAs($business)->getJson('/api/v1/voice/credits');

        $response->assertOk()
            ->assertJsonPath('data.balance_cents', 500)
            ->assertJsonPath('data.monthly_cap_cents', 1000)
            ->assertJsonPath('data.auto_call_enabled', true)
            ->assertJsonPath('data.retry_count', 3);
    }

    public function test_topup_creates_stripe_checkout_and_returns_url(): void
    {
        $business = Business::factory()->create();

        $this->mock(StripeService::class, function ($mock) use ($business) {
            $mock->shouldReceive('createVoiceCreditCheckoutSession')
                ->once()
                ->with(\Mockery::on(fn ($arg) => $arg->id === $business->id), 2000)
                ->andReturn(['id' => 'cs_123', 'url' => 'https://checkout.stripe.test/cs_123']);
        });

        $response = $this->actingAs($business)->postJson('/api/v1/voice/credits/topup', [
            'amount_cents' => 2000,
        ]);

        $response->assertOk()->assertJsonPath('checkout_url', 'https://checkout.stripe.test/cs_123');
    }

    public function test_topup_validates_amount_limits(): void
    {
        $business = Business::factory()->create();

        $response = $this->actingAs($business)->postJson('/api/v1/voice/credits/topup', [
            'amount_cents' => 50,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['amount_cents']);
    }

    public function test_set_cap_updates_cap_and_auto_renew(): void
    {
        $business = Business::factory()->create([
            'voice_monthly_cap_cents' => 0,
            'voice_auto_renew' => false,
        ]);

        $response = $this->actingAs($business)->patchJson('/api/v1/voice/credits/cap', [
            'monthly_cap_cents' => 800,
            'auto_renew' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.monthly_cap_cents', 800)
            ->assertJsonPath('data.auto_renew', true);

        $this->assertSame(800, $business->fresh()->voice_monthly_cap_cents);
        $this->assertTrue($business->fresh()->voice_auto_renew);
    }
}

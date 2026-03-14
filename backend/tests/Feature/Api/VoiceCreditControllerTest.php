<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\LeoChannel;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceCreditControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.secret' => 'sk_test_12345']);
    }

    public function test_status_returns_voice_credit_and_settings(): void
    {
        $business = Business::factory()->create([
            'voice_credit_cents' => 640,
            'voice_monthly_cap_cents' => 1500,
            'voice_auto_renew' => true,
            'voice_auto_call_enabled' => true,
            'voice_auto_call_score_threshold' => 80,
            'voice_auto_call_min_party_size' => 6,
            'voice_retry_count' => 3,
            'voice_retry_delay_minutes' => 15,
        ]);

        LeoChannel::factory()->create([
            'business_id' => $business->id,
            'channel' => 'voice',
            'is_active' => true,
        ]);

        $response = $this->actingAs($business)->getJson('/api/v1/voice/credits');

        $response->assertOk()
            ->assertJsonPath('data.balance_cents', 640)
            ->assertJsonPath('data.monthly_cap_cents', 1500)
            ->assertJsonPath('data.auto_renew', true)
            ->assertJsonPath('data.auto_call_enabled', true)
            ->assertJsonPath('data.auto_call_score_threshold', 80)
            ->assertJsonPath('data.auto_call_min_party_size', 6)
            ->assertJsonPath('data.retry_count', 3)
            ->assertJsonPath('data.retry_delay_minutes', 15)
            ->assertJsonPath('data.is_channel_active', true);
    }

    public function test_topup_returns_checkout_url(): void
    {
        $business = Business::factory()->create();

        $this->mock(StripeService::class, function ($mock) use ($business) {
            $mock->shouldReceive('createVoiceCreditCheckoutSession')
                ->with(\Mockery::on(fn ($value) => $value->id === $business->id), 2000)
                ->once()
                ->andReturn([
                    'id' => 'sess_voice_123',
                    'url' => 'https://checkout.stripe.com/pay/sess_voice_123',
                ]);
        });

        $response = $this->actingAs($business)->postJson('/api/v1/voice/credits/topup', [
            'amount_cents' => 2000,
        ]);

        $response->assertOk()
            ->assertJsonPath('checkout_url', 'https://checkout.stripe.com/pay/sess_voice_123');
    }

    public function test_topup_validates_amount_limits(): void
    {
        $business = Business::factory()->create();

        $response = $this->actingAs($business)->postJson('/api/v1/voice/credits/topup', [
            'amount_cents' => 50,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount_cents']);
    }

    public function test_set_cap_updates_cap_and_auto_renew(): void
    {
        $business = Business::factory()->create([
            'voice_monthly_cap_cents' => 0,
            'voice_auto_renew' => false,
        ]);

        $response = $this->actingAs($business)->patchJson('/api/v1/voice/credits/cap', [
            'monthly_cap_cents' => 900,
            'auto_renew' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.monthly_cap_cents', 900)
            ->assertJsonPath('data.auto_renew', true);

        $this->assertSame(900, $business->fresh()->voice_monthly_cap_cents);
        $this->assertTrue($business->fresh()->voice_auto_renew);
    }
}

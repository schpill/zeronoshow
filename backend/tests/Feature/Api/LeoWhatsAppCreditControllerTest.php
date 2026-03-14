<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\LeoChannel;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeoWhatsAppCreditControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.secret' => 'sk_test_12345']);
    }

    public function test_get_credit_status(): void
    {
        $business = Business::factory()->create([
            'whatsapp_credit_cents' => 500,
            'whatsapp_monthly_cap_cents' => 1000,
            'whatsapp_auto_renew' => true,
        ]);

        LeoChannel::factory()->create([
            'business_id' => $business->id,
            'channel' => 'whatsapp',
            'is_active' => true,
        ]);

        $response = $this->actingAs($business)->getJson('/api/v1/leo/whatsapp/credits');

        $response->assertStatus(200)
            ->assertJsonPath('data.balance_cents', 500)
            ->assertJsonPath('data.monthly_cap_cents', 1000)
            ->assertJsonPath('data.auto_renew', true)
            ->assertJsonPath('data.is_channel_active', true);
    }

    public function test_topup_returns_checkout_url(): void
    {
        $business = Business::factory()->create();

        $this->mock(StripeService::class, function ($mock) use ($business) {
            $mock->shouldReceive('createWhatsAppCreditCheckoutSession')
                ->with(\Mockery::on(fn ($b) => $b->id === $business->id), 2000)
                ->once()
                ->andReturn([
                    'id' => 'sess_123',
                    'url' => 'https://checkout.stripe.com/pay/sess_123',
                ]);
        });

        $response = $this->actingAs($business)->postJson('/api/v1/leo/whatsapp/credits/topup', [
            'amount_cents' => 2000,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('checkout_url', 'https://checkout.stripe.com/pay/sess_123');
    }

    public function test_set_cap_updates_business(): void
    {
        $business = Business::factory()->create([
            'whatsapp_monthly_cap_cents' => 0,
            'whatsapp_auto_renew' => false,
        ]);

        $response = $this->actingAs($business)->patchJson('/api/v1/leo/whatsapp/credits/cap', [
            'monthly_cap_cents' => 500,
            'auto_renew' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.monthly_cap_cents', 500)
            ->assertJsonPath('data.auto_renew', true);

        $this->assertEquals(500, $business->fresh()->whatsapp_monthly_cap_cents);
        $this->assertTrue($business->fresh()->whatsapp_auto_renew);
    }
}

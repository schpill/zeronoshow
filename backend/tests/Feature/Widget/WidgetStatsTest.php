<?php

namespace Tests\Feature\Widget;

use App\Models\BookingOtp;
use App\Models\Business;
use App\Models\Reservation;
use App\Models\WidgetSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WidgetStatsTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::factory()->create();
        WidgetSetting::create(['business_id' => $this->business->id]);
        Sanctum::actingAs($this->business);
    }

    public function test_returns_correct_total_widget_reservations_count(): void
    {
        Reservation::factory()->count(3)->create([
            'business_id' => $this->business->id,
            'source' => 'widget',
        ]);
        Reservation::factory()->create([
            'business_id' => $this->business->id,
            'source' => 'manual',
        ]);

        $response = $this->getJson('/api/v1/businesses/'.$this->business->id.'/widget/stats');

        $response->assertOk()
            ->assertJsonPath('widget_reservations_count', 3);
    }

    public function test_returns_correct_this_month_widget_count(): void
    {
        Reservation::factory()->create([
            'business_id' => $this->business->id,
            'source' => 'widget',
            'scheduled_at' => now()->startOfMonth()->addDays(2),
        ]);
        // Last month — should not be counted
        Reservation::factory()->create([
            'business_id' => $this->business->id,
            'source' => 'widget',
            'scheduled_at' => now()->subMonth(),
        ]);

        $response = $this->getJson('/api/v1/businesses/'.$this->business->id.'/widget/stats');

        $response->assertOk()
            ->assertJsonPath('widget_reservations_this_month', 1);
    }

    public function test_conversion_rate_is_computed_correctly(): void
    {
        // 2 OTPs sent in last 30 days
        BookingOtp::create([
            'phone' => '+33600000001',
            'code' => '111111',
            'expires_at' => now()->addMinutes(10),
            'created_at' => now()->subDays(5),
        ]);
        BookingOtp::create([
            'phone' => '+33600000002',
            'code' => '222222',
            'expires_at' => now()->addMinutes(10),
            'created_at' => now()->subDays(5),
        ]);

        // 1 widget reservation
        Reservation::factory()->create([
            'business_id' => $this->business->id,
            'source' => 'widget',
        ]);

        $response = $this->getJson('/api/v1/businesses/'.$this->business->id.'/widget/stats');

        $response->assertOk()
            ->assertJsonPath('widget_conversion_rate', 50);
    }

    public function test_conversion_rate_is_zero_when_no_otps_sent(): void
    {
        $response = $this->getJson('/api/v1/businesses/'.$this->business->id.'/widget/stats');

        $response->assertOk()
            ->assertJsonPath('widget_conversion_rate', 0);
    }
}

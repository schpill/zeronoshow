<?php

namespace Tests\Feature\Dashboard;

use App\Models\Business;
use App\Models\Reservation;
use App\Models\SmsLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_dashboard_metrics_for_a_specific_day(): void
    {
        $business = Business::factory()->create([
            'timezone' => 'Europe/Paris',
        ]);

        $todayReservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'confirmed',
            'scheduled_at' => now('Europe/Paris')->setTime(19, 0),
        ]);
        Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'no_show',
            'scheduled_at' => now('Europe/Paris')->addDay()->setTime(20, 0),
        ]);

        SmsLog::factory()->create([
            'business_id' => $business->id,
            'reservation_id' => $todayReservation->id,
            'cost_eur' => 0.072,
            'created_at' => now()->startOfMonth()->addDay(),
        ]);

        Sanctum::actingAs($business);

        $response = $this->getJson('/api/v1/dashboard?date='.now('Europe/Paris')->toDateString());

        $response
            ->assertOk()
            ->assertJsonCount(1, 'reservations')
            ->assertJsonPath('stats.confirmed', 1)
            ->assertJsonPath('stats.total', 1)
            ->assertJsonPath('sms_cost_this_month', 0.072)
            ->assertJsonPath('weekly_no_show_rate', 0.0);
    }

    public function test_it_aggregates_weekly_no_show_rate_for_the_selected_week(): void
    {
        $business = Business::factory()->create();

        Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'show',
            'scheduled_at' => now()->startOfWeek()->addDay()->setTime(12, 0),
        ]);
        Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'no_show',
            'scheduled_at' => now()->startOfWeek()->addDays(2)->setTime(12, 0),
        ]);

        Sanctum::actingAs($business);

        $response = $this->getJson('/api/v1/dashboard?date='.now()->toDateString());

        $response
            ->assertOk()
            ->assertJsonPath('weekly_no_show_rate', 50.0);
    }
}

<?php

namespace Tests\Feature\Dashboard;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\SmsLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    public function test_it_returns_the_full_stats_object_for_the_selected_day(): void
    {
        $business = Business::factory()->create([
            'timezone' => 'Europe/Paris',
        ]);
        $date = now('Europe/Paris')->toDateString();

        Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'confirmed',
            'scheduled_at' => now('Europe/Paris')->setTime(12, 0),
        ]);
        Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'pending_verification',
            'scheduled_at' => now('Europe/Paris')->setTime(13, 0),
        ]);
        Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'pending_reminder',
            'scheduled_at' => now('Europe/Paris')->setTime(14, 0),
        ]);
        Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'cancelled_by_client',
            'scheduled_at' => now('Europe/Paris')->setTime(15, 0),
        ]);
        Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'cancelled_no_confirmation',
            'scheduled_at' => now('Europe/Paris')->setTime(16, 0),
        ]);
        Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'show',
            'scheduled_at' => now('Europe/Paris')->setTime(17, 0),
        ]);
        Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'no_show',
            'scheduled_at' => now('Europe/Paris')->setTime(18, 0),
        ]);

        Sanctum::actingAs($business);

        $this->getJson("/api/v1/dashboard?date={$date}")
            ->assertOk()
            ->assertJsonPath('stats.confirmed', 1)
            ->assertJsonPath('stats.pending_verification', 1)
            ->assertJsonPath('stats.pending_reminder', 1)
            ->assertJsonPath('stats.cancelled', 2)
            ->assertJsonPath('stats.show', 1)
            ->assertJsonPath('stats.no_show', 1)
            ->assertJsonPath('stats.total', 7);
    }

    public function test_it_caches_the_response_for_thirty_seconds(): void
    {
        Cache::flush();

        $business = Business::factory()->create([
            'timezone' => 'Europe/Paris',
        ]);
        $date = now('Europe/Paris')->toDateString();

        Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'confirmed',
            'scheduled_at' => now('Europe/Paris')->setTime(12, 0),
        ]);
        $customer = Customer::factory()->create();

        Sanctum::actingAs($business);

        $this->getJson("/api/v1/dashboard?date={$date}")
            ->assertOk()
            ->assertJsonPath('stats.total', 1);

        DB::table('reservations')->insert([
            'id' => (string) str()->uuid(),
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'scheduled_at' => now('Europe/Paris')->setTime(13, 0)->utc(),
            'guests' => 2,
            'status' => 'confirmed',
            'customer_name' => 'Cache Test',
            'phone_verified' => true,
            'confirmation_token' => null,
            'token_expires_at' => null,
            'reminder_2h_sent' => false,
            'reminder_30m_sent' => false,
            'status_changed_at' => now()->utc(),
            'notes' => null,
            'created_at' => now()->utc(),
            'updated_at' => now()->utc(),
        ]);

        $this->getJson("/api/v1/dashboard?date={$date}")
            ->assertOk()
            ->assertJsonPath('stats.total', 1);
    }

    public function test_it_invalidates_the_dashboard_cache_after_a_reservation_status_update(): void
    {
        Cache::flush();

        $business = Business::factory()->create([
            'timezone' => 'Europe/Paris',
        ]);
        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'confirmed',
            'scheduled_at' => now('Europe/Paris')->setTime(12, 0),
        ]);
        $date = now('Europe/Paris')->toDateString();

        Sanctum::actingAs($business);

        $this->getJson("/api/v1/dashboard?date={$date}")
            ->assertOk()
            ->assertJsonPath('stats.no_show', 0);

        $this->patchJson("/api/v1/reservations/{$reservation->id}/status", [
            'status' => 'no_show',
        ])->assertOk();

        $this->getJson("/api/v1/dashboard?date={$date}")
            ->assertOk()
            ->assertJsonPath('stats.no_show', 1);
    }

    public function test_it_responds_within_five_hundred_milliseconds_for_one_hundred_reservations(): void
    {
        $business = Business::factory()->create([
            'timezone' => 'Europe/Paris',
        ]);
        $date = now('Europe/Paris')->toDateString();

        Reservation::factory()->count(100)->create([
            'business_id' => $business->id,
            'status' => 'confirmed',
            'scheduled_at' => now('Europe/Paris')->setTime(19, 0),
        ]);

        Sanctum::actingAs($business);

        $start = hrtime(true);
        $response = $this->getJson("/api/v1/dashboard?date={$date}");
        $elapsedMilliseconds = (hrtime(true) - $start) / 1_000_000;

        $response
            ->assertOk()
            ->assertJsonCount(100, 'reservations');

        $this->assertLessThan(500, $elapsedMilliseconds);
    }
}

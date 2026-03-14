<?php

namespace Tests\Unit\Leo\Tools;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use App\Leo\Tools\GetTodayStatsTool;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTodayStatsToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_today_reservations_by_status(): void
    {
        CarbonImmutable::setTestNow('2026-03-13 10:00:00');

        $business = Business::factory()->create();
        $confirmedCustomer = Customer::factory()->create([
            'reliability_score' => 80.0,
        ]);

        Reservation::factory()->create([
            'business_id' => $business->id,
            'customer_id' => $confirmedCustomer->id,
            'scheduled_at' => now()->addHour(),
            'status' => 'confirmed',
        ]);
        Reservation::factory()->create(['business_id' => $business->id, 'scheduled_at' => now()->addHours(2), 'status' => 'pending_verification']);
        Reservation::factory()->create(['business_id' => $business->id, 'scheduled_at' => now()->addHours(3), 'status' => 'pending_reminder']);
        Reservation::factory()->create(['business_id' => $business->id, 'scheduled_at' => now()->addHours(4), 'status' => 'cancelled_by_client']);
        Reservation::factory()->create(['business_id' => $business->id, 'scheduled_at' => now()->addHours(5), 'status' => 'no_show']);
        Reservation::factory()->create(['business_id' => $business->id, 'scheduled_at' => now()->addHours(6), 'status' => 'show']);
        Reservation::factory()->create(['business_id' => $business->id, 'scheduled_at' => now()->addDay(), 'status' => 'confirmed']);

        $result = (new GetTodayStatsTool)->execute($business->id);

        $this->assertSame([
            'total' => 6,
            'confirmed' => 1,
            'pending' => 2,
            'cancelled' => 1,
            'no_show' => 1,
            'show' => 1,
            'score_avg' => 80.0,
        ], $result);

        CarbonImmutable::setTestNow();
    }

    public function test_it_returns_the_average_reliability_score_for_confirmed_reservations(): void
    {
        CarbonImmutable::setTestNow('2026-03-13 10:00:00');

        $business = Business::factory()->create();
        $firstCustomer = Customer::factory()->create([
            'reliability_score' => 80.0,
        ]);
        $secondCustomer = Customer::factory()->create([
            'reliability_score' => 92.0,
        ]);

        Reservation::factory()->create([
            'business_id' => $business->id,
            'customer_id' => $firstCustomer->id,
            'scheduled_at' => now()->addHour(),
            'status' => 'confirmed',
        ]);
        Reservation::factory()->create([
            'business_id' => $business->id,
            'customer_id' => $secondCustomer->id,
            'scheduled_at' => now()->addHours(2),
            'status' => 'confirmed',
        ]);

        $result = (new GetTodayStatsTool)->execute($business->id);

        $this->assertSame(86.0, $result['score_avg']);

        CarbonImmutable::setTestNow();
    }

    public function test_it_returns_null_score_avg_when_no_confirmed_reservations_exist(): void
    {
        CarbonImmutable::setTestNow('2026-03-13 10:00:00');

        $business = Business::factory()->create();

        Reservation::factory()->create([
            'business_id' => $business->id,
            'scheduled_at' => now()->addHour(),
            'status' => 'pending_verification',
        ]);

        $result = (new GetTodayStatsTool)->execute($business->id);

        $this->assertNull($result['score_avg']);

        CarbonImmutable::setTestNow();
    }
}

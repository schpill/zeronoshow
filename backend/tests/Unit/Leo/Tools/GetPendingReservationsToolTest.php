<?php

namespace Tests\Unit\Leo\Tools;

use App\Leo\Tools\GetPendingReservationsTool;
use App\Models\Business;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetPendingReservationsToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_today_pending_reservations_ordered_without_phone_numbers(): void
    {
        CarbonImmutable::setTestNow('2026-03-13 10:00:00');

        $business = Business::factory()->create();

        Reservation::factory()->create([
            'business_id' => $business->id,
            'scheduled_at' => now()->addHours(3),
            'customer_name' => 'Alice',
            'status' => 'pending_reminder',
            'guests' => 4,
        ]);
        Reservation::factory()->create([
            'business_id' => $business->id,
            'scheduled_at' => now()->addHour(),
            'customer_name' => 'Bob',
            'status' => 'pending_verification',
            'guests' => 2,
        ]);
        Reservation::factory()->create([
            'business_id' => $business->id,
            'scheduled_at' => now()->addHours(2),
            'customer_name' => 'Charlie',
            'status' => 'confirmed',
        ]);

        $result = (new GetPendingReservationsTool)->execute($business->id);

        $this->assertSame('Bob', $result[0]['name']);
        $this->assertSame('Alice', $result[1]['name']);
        $this->assertArrayNotHasKey('phone', $result[0]);

        CarbonImmutable::setTestNow();
    }
}

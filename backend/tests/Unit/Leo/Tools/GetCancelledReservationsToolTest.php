<?php

namespace Tests\Unit\Leo\Tools;

use App\Models\Business;
use App\Models\Reservation;
use App\Services\Leo\GetCancelledReservationsTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetCancelledReservationsToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_today_cancelled_reservations_for_both_statuses_without_phone_numbers(): void
    {
        $business = Business::factory()->create();

        Reservation::factory()->create([
            'business_id' => $business->id,
            'scheduled_at' => now()->addHour(),
            'customer_name' => 'Alice',
            'status' => 'cancelled_by_client',
            'status_changed_at' => now()->subMinutes(10),
        ]);
        Reservation::factory()->create([
            'business_id' => $business->id,
            'scheduled_at' => now()->addHours(2),
            'customer_name' => 'Bob',
            'status' => 'cancelled_no_confirmation',
            'status_changed_at' => now()->subMinutes(20),
        ]);
        Reservation::factory()->create([
            'business_id' => $business->id,
            'scheduled_at' => now()->addHours(3),
            'customer_name' => 'Charlie',
            'status' => 'confirmed',
        ]);

        $result = (new GetCancelledReservationsTool)->execute($business->id);

        $this->assertCount(2, $result);
        $this->assertSame('Alice', $result[0]['name']);
        $this->assertSame('Bob', $result[1]['name']);
        $this->assertArrayNotHasKey('phone', $result[0]);
    }
}

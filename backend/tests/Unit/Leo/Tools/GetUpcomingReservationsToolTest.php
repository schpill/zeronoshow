<?php

namespace Tests\Unit\Leo\Tools;

use App\Models\Business;
use App\Models\Reservation;
use App\Leo\Tools\GetUpcomingReservationsTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetUpcomingReservationsToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_future_reservations_and_respects_the_limit(): void
    {
        $business = Business::factory()->create();

        Reservation::factory()->create(['business_id' => $business->id, 'scheduled_at' => now()->subHour(), 'customer_name' => 'Past']);
        Reservation::factory()->create(['business_id' => $business->id, 'scheduled_at' => now()->addHour(), 'customer_name' => 'First']);
        Reservation::factory()->create(['business_id' => $business->id, 'scheduled_at' => now()->addHours(2), 'customer_name' => 'Second']);
        Reservation::factory()->create(['business_id' => $business->id, 'scheduled_at' => now()->addHours(3), 'customer_name' => 'Third']);

        $result = (new GetUpcomingReservationsTool)->execute($business->id, 2);

        $this->assertCount(2, $result);
        $this->assertSame('First', $result[0]['name']);
        $this->assertSame('Second', $result[1]['name']);
    }
}

<?php

namespace Tests\Unit\Leo\Tools;

use App\Leo\Tools\GetReservationDetailsTool;
use App\Models\Business;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetReservationDetailsToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_finds_today_reservations_by_name_or_time_without_phone_numbers(): void
    {
        $business = Business::factory()->create();
        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'customer_name' => 'Alice Martin',
            'scheduled_at' => now()->setTime(19, 30),
            'status' => 'confirmed',
            'notes' => 'Près de la fenêtre',
        ]);
        Reservation::factory()->create([
            'business_id' => $business->id,
            'customer_name' => 'Bob',
            'scheduled_at' => now()->setTime(20, 0),
            'status' => 'confirmed',
        ]);

        $tool = new GetReservationDetailsTool;

        $byName = $tool->execute($business->id, 'alice');
        $byTime = $tool->execute($business->id, $reservation->scheduled_at->format('H:i'));

        $this->assertCount(1, $byName);
        $this->assertSame('Alice Martin', $byName[0]['name']);
        $this->assertSame('Alice Martin', $byTime[0]['name']);
        $this->assertArrayNotHasKey('phone', $byName[0]);
    }
}

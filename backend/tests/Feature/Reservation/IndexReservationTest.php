<?php

namespace Tests\Feature\Reservation;

use App\Models\Business;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IndexReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_reservations_filtered_by_date(): void
    {
        $business = Business::factory()->create();
        Sanctum::actingAs($business);

        Reservation::factory()->create([
            'business_id' => $business->id,
            'scheduled_at' => now()->addDay()->setTime(19, 0),
        ]);

        Reservation::factory()->create([
            'business_id' => $business->id,
            'scheduled_at' => now()->addDays(2)->setTime(19, 0),
        ]);

        $response = $this->getJson('/api/v1/reservations?date='.now()->addDay()->toDateString());

        $response
            ->assertOk()
            ->assertJsonCount(1, 'reservations')
            ->assertJsonPath('stats.total', 1);
    }
}

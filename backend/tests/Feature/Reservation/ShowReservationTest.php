<?php

namespace Tests\Feature\Reservation;

use App\Models\Business;
use App\Models\Reservation;
use App\Models\SmsLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShowReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_reservation_with_customer_and_sms_logs(): void
    {
        $business = Business::factory()->create();
        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
        ]);
        SmsLog::factory()->create([
            'reservation_id' => $reservation->id,
            'business_id' => $business->id,
        ]);

        Sanctum::actingAs($business);

        $response = $this->getJson("/api/v1/reservations/{$reservation->id}");

        $response
            ->assertOk()
            ->assertJsonPath('reservation.id', $reservation->id)
            ->assertJsonPath('customer.phone', $reservation->customer->phone)
            ->assertJsonCount(1, 'sms_logs');
    }
}

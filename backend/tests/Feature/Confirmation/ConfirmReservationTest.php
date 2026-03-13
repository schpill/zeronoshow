<?php

namespace Tests\Feature\Confirmation;

use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ConfirmReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_confirms_a_reservation_and_invalidates_the_token(): void
    {
        Queue::fake();
        $reservation = Reservation::factory()->create();

        $response = $this->post("/c/{$reservation->confirmation_token}/confirm", [
            'action' => 'confirm',
        ]);

        $response->assertOk()->assertSee('Réservation confirmée');
        $reservation->refresh();

        $this->assertSame('confirmed', $reservation->status);
        $this->assertNull($reservation->confirmation_token);
    }

    public function test_it_cancels_a_reservation_via_get_cancel_link(): void
    {
        Queue::fake();
        $reservation = Reservation::factory()->create();

        $response = $this->get("/c/{$reservation->confirmation_token}/cancel");

        $response->assertOk()->assertSee('Réservation annulée');
        $reservation->refresh();

        $this->assertSame('cancelled_by_client', $reservation->status);
        $this->assertNull($reservation->confirmation_token);
    }
}

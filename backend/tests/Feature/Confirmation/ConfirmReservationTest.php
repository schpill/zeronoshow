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
}

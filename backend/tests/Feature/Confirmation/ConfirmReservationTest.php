<?php

namespace Tests\Feature\Confirmation;

use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ConfirmReservationTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

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
        $this->assertNotNull($reservation->confirmation_token);
    }

    public function test_it_cancels_a_reservation_via_get_cancel_link(): void
    {
        Queue::fake();
        $reservation = Reservation::factory()->create();

        $response = $this->get("/c/{$reservation->confirmation_token}/cancel");

        $response->assertOk()->assertSee('Réservation annulée');
        $reservation->refresh();

        $this->assertSame('cancelled_by_client', $reservation->status);
        $this->assertNotNull($reservation->confirmation_token);
    }

    public function test_it_returns_an_idempotent_message_when_a_confirmed_reservation_is_confirmed_again(): void
    {
        Queue::fake();
        $reservation = Reservation::factory()->create([
            'status' => 'confirmed',
        ]);

        $response = $this->post("/c/{$reservation->confirmation_token}/confirm", [
            'action' => 'confirm',
        ]);

        $response->assertOk()->assertSee('Vous avez déjà confirmé ce rendez-vous');
    }

    public function test_it_returns_an_idempotent_message_when_a_cancelled_reservation_is_cancelled_again(): void
    {
        Queue::fake();
        $reservation = Reservation::factory()->create([
            'status' => 'cancelled_by_client',
        ]);

        $response = $this->post("/c/{$reservation->confirmation_token}/confirm", [
            'action' => 'cancel',
        ]);

        $response->assertOk()->assertSee('Vous avez déjà annulé ce rendez-vous');
    }

    public function test_it_returns_gone_for_terminal_reservation_states(): void
    {
        Queue::fake();
        $reservation = Reservation::factory()->create([
            'status' => 'show',
        ]);

        $response = $this->post("/c/{$reservation->confirmation_token}/confirm", [
            'action' => 'confirm',
        ]);

        $response->assertStatus(410)->assertSee('Cette réservation ne peut plus être modifiée');
    }
}

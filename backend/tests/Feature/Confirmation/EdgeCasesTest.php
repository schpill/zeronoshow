<?php

namespace Tests\Feature\Confirmation;

use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_confirmation_returns_an_idempotent_message_without_invalidating_the_token(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'confirmed',
        ]);

        $this->post("/c/{$reservation->confirmation_token}/confirm", [
            'action' => 'confirm',
        ])->assertOk()->assertSee('Vous avez déjà confirmé ce rendez-vous');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'confirmation_token' => $reservation->confirmation_token,
        ]);
    }

    public function test_duplicate_cancellation_returns_an_idempotent_message_without_invalidating_the_token(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'cancelled_by_client',
        ]);

        $this->post("/c/{$reservation->confirmation_token}/confirm", [
            'action' => 'cancel',
        ])->assertOk()->assertSee('Vous avez déjà annulé ce rendez-vous');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'confirmation_token' => $reservation->confirmation_token,
        ]);
    }

    public function test_terminal_states_return_gone(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'show',
        ]);

        $this->post("/c/{$reservation->confirmation_token}/confirm", [
            'action' => 'confirm',
        ])->assertStatus(410)->assertSee('Cette réservation ne peut plus être modifiée');
    }
}

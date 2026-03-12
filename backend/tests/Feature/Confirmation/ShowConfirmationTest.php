<?php

namespace Tests\Feature\Confirmation;

use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_confirmation_page_for_valid_token(): void
    {
        $reservation = Reservation::factory()->create();

        $response = $this->get("/c/{$reservation->confirmation_token}");

        $response->assertOk()->assertSee('Confirmez votre réservation');
    }

    public function test_it_returns_gone_for_expired_token(): void
    {
        $reservation = Reservation::factory()->create([
            'token_expires_at' => now()->subMinute(),
        ]);

        $response = $this->get("/c/{$reservation->confirmation_token}");

        $response->assertStatus(410);
    }
}

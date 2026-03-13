<?php

namespace Tests\Feature\Commands;

use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoCancelExpiredTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_cancels_reservations_with_expired_tokens(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'pending_verification',
            'token_expires_at' => now()->subMinute(),
        ]);

        $this->artisan('reservations:auto-cancel')
            ->expectsOutputToContain('1 token')
            ->assertExitCode(0);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled_no_confirmation',
        ]);
    }

    public function test_it_cancels_unconfirmed_reservations_fifteen_minutes_after_the_slot(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'pending_reminder',
            'reminder_30m_sent' => true,
            'scheduled_at' => now()->subMinutes(16),
            'confirmation_token' => (string) fake()->uuid(),
        ]);

        $this->artisan('reservations:auto-cancel')->assertExitCode(0);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled_no_confirmation',
        ]);
    }

    public function test_it_does_not_cancel_already_confirmed_reservations(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'confirmed',
            'scheduled_at' => now()->subMinutes(30),
        ]);

        $this->artisan('reservations:auto-cancel')->assertExitCode(0);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_it_cancels_confirmed_reservations_that_are_still_unconfirmed_after_the_last_reminder(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'confirmed',
            'reminder_30m_sent' => true,
            'scheduled_at' => now()->subMinutes(16),
            'confirmation_token' => (string) fake()->uuid(),
        ]);

        $this->artisan('reservations:auto-cancel')->assertExitCode(0);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled_no_confirmation',
        ]);
    }
}

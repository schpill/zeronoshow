<?php

namespace Tests\Feature\Reservation;

use App\Jobs\SendVerificationSms;
use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_an_immediate_confirmation_sms_for_appointments_less_than_two_hours_away(): void
    {
        Queue::fake();
        $business = Business::factory()->create();
        Sanctum::actingAs($business);

        $response = $this->postJson('/api/v1/reservations', [
            'customer_name' => 'Marc Dubois',
            'phone' => '+33612345678',
            'scheduled_at' => now()->addMinutes(90)->toIso8601String(),
            'guests' => 2,
            'phone_verified' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('reservation.status', 'pending_verification')
            ->assertJsonPath('warning', null);

        Queue::assertPushed(SendVerificationSms::class);
    }

    public function test_it_records_without_sms_for_appointments_less_than_thirty_minutes_away(): void
    {
        Queue::fake();
        $business = Business::factory()->create();
        Sanctum::actingAs($business);

        $response = $this->postJson('/api/v1/reservations', [
            'customer_name' => 'Marc Dubois',
            'phone' => '+33612345678',
            'scheduled_at' => now()->addMinutes(20)->toIso8601String(),
            'guests' => 2,
            'phone_verified' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('warning', 'Appointment too soon for SMS confirmation')
            ->assertJsonPath('reservation.confirmation_token', null);

        Queue::assertNothingPushed();
    }
}

<?php

namespace Tests\Feature\Reservation;

use App\Jobs\SendVerificationSms;
use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_unverified_reservation_and_dispatches_sms_job(): void
    {
        Queue::fake();
        $business = Business::factory()->create();
        Sanctum::actingAs($business);

        $response = $this->postJson('/api/v1/reservations', [
            'customer_name' => 'Marc Dubois',
            'phone' => '+33612345678',
            'scheduled_at' => Carbon::now()->addDay()->toIso8601String(),
            'guests' => 2,
            'phone_verified' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('reservation.status', 'pending_verification');

        Queue::assertPushed(SendVerificationSms::class);
        $this->assertDatabaseHas('customers', ['phone' => '+33612345678']);
    }

    public function test_it_skips_sms_when_phone_is_verified(): void
    {
        Queue::fake();
        $business = Business::factory()->create();
        Sanctum::actingAs($business);

        $response = $this->postJson('/api/v1/reservations', [
            'customer_name' => 'Marc Dubois',
            'phone' => '+33612345678',
            'scheduled_at' => Carbon::now()->addDay()->toIso8601String(),
            'guests' => 2,
            'phone_verified' => true,
        ]);

        $response->assertCreated()->assertJsonPath('reservation.status', 'pending_reminder');
        Queue::assertNothingPushed();
    }
}

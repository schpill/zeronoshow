<?php

namespace Tests\Feature\Reservation;

use App\Jobs\SendVerificationSms;
use App\Models\Business;
use App\Models\Customer;
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

        $response
            ->assertCreated()
            ->assertJsonPath('reservation.status', 'pending_reminder')
            ->assertJsonPath('reservation.phone_verified', true);

        Queue::assertNothingPushed();
        $this->assertDatabaseHas('reservations', [
            'business_id' => $business->id,
            'status' => 'pending_reminder',
            'phone_verified' => true,
        ]);
        $this->assertNotNull($response->json('reservation.confirmation_token'));
    }

    public function test_it_returns_the_existing_customer_reliability_score(): void
    {
        Queue::fake();
        $business = Business::factory()->create();
        $customer = Customer::factory()->create([
            'phone' => '+33612345678',
            'reliability_score' => 94,
        ]);
        Sanctum::actingAs($business);

        $response = $this->postJson('/api/v1/reservations', [
            'customer_name' => 'Marc Dubois',
            'phone' => $customer->phone,
            'scheduled_at' => Carbon::now()->addDay()->toIso8601String(),
            'guests' => 2,
            'phone_verified' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('customer.reliability_score', 94)
            ->assertJsonPath('customer.score_tier', 'reliable')
            ->assertJsonPath('customer.opted_out', false);
    }

    public function test_it_returns_no_history_when_existing_customer_has_no_score(): void
    {
        Queue::fake();
        $business = Business::factory()->create();
        $customer = Customer::factory()->create([
            'phone' => '+33612345678',
            'reliability_score' => null,
        ]);
        Sanctum::actingAs($business);

        $response = $this->postJson('/api/v1/reservations', [
            'customer_name' => 'Marc Dubois',
            'phone' => $customer->phone,
            'scheduled_at' => Carbon::now()->addDay()->toIso8601String(),
            'guests' => 2,
            'phone_verified' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('customer.reliability_score', null)
            ->assertJsonPath('customer.score_tier', 'at_risk')
            ->assertJsonPath('customer.opted_out', false);
    }

    public function test_it_reuses_an_existing_customer_by_phone(): void
    {
        Queue::fake();
        $business = Business::factory()->create();
        $customer = Customer::factory()->create([
            'phone' => '+33612345678',
            'reservations_count' => 3,
        ]);
        Sanctum::actingAs($business);

        $this->postJson('/api/v1/reservations', [
            'customer_name' => 'Marc Dubois',
            'phone' => $customer->phone,
            'scheduled_at' => Carbon::now()->addDay()->toIso8601String(),
            'guests' => 2,
            'phone_verified' => false,
        ])->assertCreated();

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'phone' => $customer->phone,
            'reservations_count' => 4,
        ]);
    }

    public function test_it_rejects_a_past_appointment_date(): void
    {
        Queue::fake();
        $business = Business::factory()->create();
        Sanctum::actingAs($business);

        $response = $this->postJson('/api/v1/reservations', [
            'customer_name' => 'Marc Dubois',
            'phone' => '+33612345678',
            'scheduled_at' => Carbon::now()->subHour()->toIso8601String(),
            'guests' => 2,
            'phone_verified' => false,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_at']);
    }

    public function test_it_rejects_an_invalid_e164_phone_number(): void
    {
        Queue::fake();
        $business = Business::factory()->create();
        Sanctum::actingAs($business);

        $response = $this->postJson('/api/v1/reservations', [
            'customer_name' => 'Marc Dubois',
            'phone' => '0612345678',
            'scheduled_at' => Carbon::now()->addDay()->toIso8601String(),
            'guests' => 2,
            'phone_verified' => false,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_it_returns_402_when_the_business_subscription_is_expired(): void
    {
        Queue::fake();
        $business = Business::factory()->create([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->subDay(),
        ]);
        Sanctum::actingAs($business);

        $response = $this->postJson('/api/v1/reservations', [
            'customer_name' => 'Marc Dubois',
            'phone' => '+33612345678',
            'scheduled_at' => Carbon::now()->addDay()->toIso8601String(),
            'guests' => 2,
            'phone_verified' => false,
        ]);

        $response->assertStatus(402);
        Queue::assertNothingPushed();
    }
}

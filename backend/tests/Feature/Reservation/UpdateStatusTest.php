<?php

namespace Tests\Feature\Reservation;

use App\Jobs\RecalculateReliabilityScore;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_a_reservation_as_show_and_returns_updated_score(): void
    {
        Queue::fake();
        $business = Business::factory()->create();
        $customer = Customer::factory()->create([
            'shows_count' => 6,
            'no_shows_count' => 2,
        ]);
        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'status' => 'confirmed',
        ]);
        Sanctum::actingAs($business);

        $response = $this->patchJson("/api/v1/reservations/{$reservation->id}/status", [
            'status' => 'show',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('reservation.status', 'show')
            ->assertJsonPath('customer.score_tier', 'average');

        Queue::assertPushed(RecalculateReliabilityScore::class);
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'show',
        ]);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'shows_count' => 7,
        ]);
    }

    public function test_it_marks_a_reservation_as_no_show_and_updates_customer_counters(): void
    {
        Queue::fake();
        $business = Business::factory()->create();
        $customer = Customer::factory()->create();
        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'status' => 'pending_reminder',
        ]);
        Sanctum::actingAs($business);

        $response = $this->patchJson("/api/v1/reservations/{$reservation->id}/status", [
            'status' => 'no_show',
        ]);

        $response->assertOk()->assertJsonPath('reservation.status', 'no_show');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'no_shows_count' => 1,
        ]);
    }

    public function test_it_returns_forbidden_for_a_reservation_from_another_business(): void
    {
        Queue::fake();
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $reservation = Reservation::factory()->create([
            'business_id' => $otherBusiness->id,
        ]);
        Sanctum::actingAs($business);

        $this->patchJson("/api/v1/reservations/{$reservation->id}/status", [
            'status' => 'show',
        ])->assertForbidden();
    }

    public function test_it_rejects_invalid_status_values(): void
    {
        Queue::fake();
        $business = Business::factory()->create();
        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
        ]);
        Sanctum::actingAs($business);

        $this->patchJson("/api/v1/reservations/{$reservation->id}/status", [
            'status' => 'confirmed',
        ])->assertStatus(422)->assertJsonValidationErrors(['status']);
    }
}

<?php

namespace Tests\Feature\Voice;

use App\Models\Business;
use App\Models\Reservation;
use App\Models\VoiceCallLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VoiceCallControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.twilio.sid' => 'AC123',
            'services.twilio.token' => 'token-123',
            'services.twilio.voice_number' => '+33123456789',
            'services.twilio.voice_cost_per_call_cents' => 8,
            'app.url' => 'https://api.example.test',
        ]);
    }

    public function test_initiate_places_a_call_and_returns_accepted(): void
    {
        Http::fake([
            'https://api.twilio.com/*' => Http::response(['sid' => 'CA123'], 201),
        ]);

        $business = Business::factory()->create([
            'voice_credit_cents' => 100,
        ]);
        $reservation = Reservation::factory()->for($business)->create([
            'status' => 'pending_verification',
        ]);

        $response = $this->actingAs($business)->postJson("/api/v1/reservations/{$reservation->id}/call");

        $response->assertStatus(202)
            ->assertJsonPath('data.reservation_id', $reservation->id)
            ->assertJsonPath('data.status', 'initiated');
    }

    public function test_initiate_returns_not_found_for_another_business_reservation(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $reservation = Reservation::factory()->for($otherBusiness)->create();

        $this->actingAs($business)
            ->postJson("/api/v1/reservations/{$reservation->id}/call")
            ->assertNotFound();
    }

    public function test_logs_returns_calls_ordered_by_created_at(): void
    {
        $business = Business::factory()->create();
        $reservation = Reservation::factory()->for($business)->create();

        $first = VoiceCallLog::query()->create([
            'reservation_id' => $reservation->id,
            'business_id' => $business->id,
            'to_phone' => $reservation->customer->phone,
            'attempt_number' => 1,
            'status' => 'no_answer',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);
        $second = VoiceCallLog::query()->create([
            'reservation_id' => $reservation->id,
            'business_id' => $business->id,
            'to_phone' => $reservation->customer->phone,
            'attempt_number' => 2,
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($business)->getJson("/api/v1/reservations/{$reservation->id}/calls");

        $response->assertOk()
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.1.id', $second->id);
    }
}

<?php

namespace Tests\Feature\Voice;

use App\Models\Business;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_twiml_endpoint_returns_french_gather_message(): void
    {
        $business = Business::factory()->create();
        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'customer_name' => 'Jean Martin',
        ]);

        $logId = (string) \Str::uuid();
        \DB::table('voice_call_logs')->insert([
            'id' => $logId,
            'reservation_id' => $reservation->id,
            'business_id' => $business->id,
            'to_phone' => $reservation->customer->phone,
            'attempt_number' => 1,
            'status' => 'initiated',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get("/api/v1/webhooks/leo/voice/twiml/{$logId}");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
        $response->assertSee('Polly.Léa', false);
        $response->assertSee('Appuyez sur 1 pour confirmer', false);
    }

    public function test_gather_endpoint_confirms_reservation_on_digit_1(): void
    {
        $business = Business::factory()->create();
        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'pending_reminder',
        ]);

        $logId = (string) \Str::uuid();
        \DB::table('voice_call_logs')->insert([
            'id' => $logId,
            'reservation_id' => $reservation->id,
            'business_id' => $business->id,
            'to_phone' => $reservation->customer->phone,
            'attempt_number' => 1,
            'status' => 'answered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->post("/api/v1/webhooks/leo/voice/gather/{$logId}", [
            'Digits' => '1',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'confirmed',
        ]);

        $this->assertDatabaseHas('voice_call_logs', [
            'id' => $logId,
            'status' => 'confirmed',
            'dtmf_response' => '1',
        ]);
    }

    public function test_gather_endpoint_cancels_reservation_on_digit_2(): void
    {
        $business = Business::factory()->create();
        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'pending_reminder',
        ]);

        $logId = (string) \Str::uuid();
        \DB::table('voice_call_logs')->insert([
            'id' => $logId,
            'reservation_id' => $reservation->id,
            'business_id' => $business->id,
            'to_phone' => $reservation->customer->phone,
            'attempt_number' => 1,
            'status' => 'answered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->post("/api/v1/webhooks/leo/voice/gather/{$logId}", [
            'Digits' => '2',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled_by_client',
        ]);

        $this->assertDatabaseHas('voice_call_logs', [
            'id' => $logId,
            'status' => 'declined',
            'dtmf_response' => '2',
        ]);
    }
}

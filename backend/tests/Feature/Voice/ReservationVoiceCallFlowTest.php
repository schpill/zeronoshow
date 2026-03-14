<?php

namespace Tests\Feature\Voice;

use App\Jobs\PlaceVoiceCallJob;
use App\Models\Business;
use App\Models\LeoChannel;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReservationVoiceCallFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_voice_call_dispatches_job_for_reservation(): void
    {
        Queue::fake();

        $business = Business::factory()->create([
            'voice_credit_cents' => 500,
            'voice_monthly_cap_cents' => 1000,
        ]);

        LeoChannel::factory()->create([
            'business_id' => $business->id,
            'channel' => 'voice',
            'external_identifier' => '+33123456789',
        ]);

        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'pending_reminder',
        ]);

        Sanctum::actingAs($business);

        $response = $this->postJson("/api/v1/reservations/{$reservation->id}/voice-call");

        $response->assertOk()
            ->assertJsonPath('queued', true);

        Queue::assertPushed(PlaceVoiceCallJob::class, function (PlaceVoiceCallJob $job) use ($reservation): bool {
            return $job->reservationId === $reservation->id && $job->attemptNumber === 1;
        });
    }

    public function test_auto_call_dispatches_job_when_business_rules_match(): void
    {
        Queue::fake();

        $business = Business::factory()->create([
            'voice_credit_cents' => 500,
            'voice_monthly_cap_cents' => 1000,
            'voice_auto_call_enabled' => true,
            'voice_auto_call_score_threshold' => 80,
            'voice_auto_call_min_party_size' => null,
        ]);

        LeoChannel::factory()->create([
            'business_id' => $business->id,
            'channel' => 'voice',
            'external_identifier' => '+33123456789',
        ]);

        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'pending_reminder',
            'guests' => 4,
        ]);

        $reservation->customer()->update([
            'reliability_score' => 72,
        ]);

        $reservation->update([
            'notes' => 'trigger voice re-evaluation',
        ]);

        Queue::assertPushed(PlaceVoiceCallJob::class, function (PlaceVoiceCallJob $job) use ($reservation): bool {
            return $job->reservationId === $reservation->id;
        });
    }

    public function test_show_reservation_includes_voice_call_logs(): void
    {
        $business = Business::factory()->create();
        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
        ]);

        \DB::table('voice_call_logs')->insert([
            'id' => (string) \Str::uuid(),
            'reservation_id' => $reservation->id,
            'business_id' => $business->id,
            'to_phone' => $reservation->customer->phone,
            'attempt_number' => 1,
            'status' => 'answered',
            'dtmf_response' => '1',
            'duration_seconds' => 41,
            'cost_cents' => 8,
            'twilio_call_sid' => 'CA12345678901234567890123456789012',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($business);

        $response = $this->getJson("/api/v1/reservations/{$reservation->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'voice_call_logs')
            ->assertJsonPath('voice_call_logs.0.status', 'answered')
            ->assertJsonPath('voice_call_logs.0.duration_seconds', 41);
    }
}

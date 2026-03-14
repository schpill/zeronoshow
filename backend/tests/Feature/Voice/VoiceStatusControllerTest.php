<?php

namespace Tests\Feature\Voice;

use App\Jobs\PlaceVoiceCallJob;
use App\Models\Business;
use App\Models\Reservation;
use App\Models\VoiceCallLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class VoiceStatusControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_answer_with_remaining_retries_dispatches_a_new_attempt(): void
    {
        Queue::fake();

        $log = $this->createVoiceLog(1, ['voice_retry_count' => 3, 'voice_retry_delay_minutes' => 10]);

        $response = $this->post('/api/v1/webhooks/voice/status/'.$log->id, [
            'CallStatus' => 'no-answer',
        ]);

        $response->assertOk();
        $this->assertSame('no_answer', $log->fresh()->status->value);
        Queue::assertPushed(PlaceVoiceCallJob::class, fn (PlaceVoiceCallJob $job) => $job->attemptNumber === 2);
    }

    public function test_no_answer_on_last_attempt_does_not_dispatch_a_retry(): void
    {
        Queue::fake();

        $log = $this->createVoiceLog(3, ['voice_retry_count' => 3]);

        $response = $this->post('/api/v1/webhooks/voice/status/'.$log->id, [
            'CallStatus' => 'busy',
        ]);

        $response->assertOk();
        $this->assertSame('no_answer', $log->fresh()->status->value);
        Queue::assertNotPushed(PlaceVoiceCallJob::class);
    }

    public function test_completed_status_updates_log_to_answered_and_sets_duration(): void
    {
        $log = $this->createVoiceLog();

        $response = $this->post('/api/v1/webhooks/voice/status/'.$log->id, [
            'CallStatus' => 'completed',
            'CallDuration' => '37',
        ]);

        $response->assertOk();
        $this->assertSame('answered', $log->fresh()->status->value);
        $this->assertSame(37, $log->fresh()->duration_seconds);
    }

    private function createVoiceLog(int $attemptNumber = 1, array $businessOverrides = []): VoiceCallLog
    {
        $business = Business::factory()->create(array_merge([
            'voice_retry_count' => 3,
            'voice_retry_delay_minutes' => 10,
        ], $businessOverrides));
        $reservation = Reservation::factory()->for($business)->create();

        return VoiceCallLog::query()->create([
            'reservation_id' => $reservation->id,
            'business_id' => $business->id,
            'to_phone' => $reservation->customer->phone,
            'attempt_number' => $attemptNumber,
            'status' => 'initiated',
        ]);
    }
}

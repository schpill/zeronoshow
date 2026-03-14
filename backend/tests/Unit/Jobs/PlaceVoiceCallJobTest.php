<?php

namespace Tests\Unit\Jobs;

use App\Exceptions\VoiceInsufficientCreditException;
use App\Jobs\PlaceVoiceCallJob;
use App\Models\Reservation;
use App\Services\VoiceCallService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PlaceVoiceCallJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_skips_non_pending_reservations(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'confirmed',
        ]);

        $service = $this->mock(VoiceCallService::class);
        $service->shouldNotReceive('initiateCall');

        $job = new PlaceVoiceCallJob($reservation->id, 1);
        $job->handle($service);
    }

    public function test_it_places_a_call_for_pending_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'pending_verification',
        ]);

        $service = $this->mock(VoiceCallService::class);
        $service->shouldReceive('initiateCall')
            ->once()
            ->withArgs(fn ($arg, $attempt) => $arg->is($reservation) && $attempt === 2);

        $job = new PlaceVoiceCallJob($reservation->id, 2);
        $job->handle($service);
    }

    public function test_it_logs_and_swallow_insufficient_credit_exceptions(): void
    {
        Log::spy();

        $reservation = Reservation::factory()->create([
            'status' => 'pending_verification',
        ]);

        $service = $this->mock(VoiceCallService::class);
        $service->shouldReceive('initiateCall')
            ->once()
            ->andThrow(new VoiceInsufficientCreditException('not enough credit'));

        $job = new PlaceVoiceCallJob($reservation->id, 1);
        $job->handle($service);

        Log::shouldHaveReceived('warning')->once();
    }
}

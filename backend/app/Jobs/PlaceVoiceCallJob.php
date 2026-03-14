<?php

namespace App\Jobs;

use App\Exceptions\VoiceInsufficientCreditException;
use App\Models\Reservation;
use App\Services\VoiceCallService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PlaceVoiceCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly string $reservationId,
        public readonly int $attemptNumber = 1,
    ) {
        $this->onQueue('default');
    }

    public function handle(VoiceCallService $voiceCallService): void
    {
        $reservation = Reservation::query()->with(['business', 'customer'])->find($this->reservationId);

        if (! $reservation || ! in_array($reservation->status, ['pending_verification', 'pending_reminder'], true)) {
            return;
        }

        try {
            $voiceCallService->initiateCall($reservation, $this->attemptNumber);
        } catch (VoiceInsufficientCreditException $exception) {
            Log::warning('Voice call skipped because credit is insufficient.', [
                'reservation_id' => $this->reservationId,
                'attempt_number' => $this->attemptNumber,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

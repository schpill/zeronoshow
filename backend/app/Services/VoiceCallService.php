<?php

namespace App\Services;

use App\Enums\VoiceCallStatusEnum;
use App\Exceptions\VoiceInsufficientCreditException;
use App\Models\Reservation;
use App\Models\VoiceCallLog;
use Illuminate\Support\Facades\Http;

class VoiceCallService
{
    public function __construct(
        private readonly VoiceCreditService $credits,
    ) {}

    public function initiateCall(Reservation $reservation, int $attemptNumber = 1): VoiceCallLog
    {
        $reservation->loadMissing(['business', 'customer']);

        $costCents = $this->credits->getCallCost();

        if (! $this->credits->hasSufficientCredit($reservation->business, $costCents)) {
            throw new VoiceInsufficientCreditException('Insufficient voice credit.');
        }

        $log = VoiceCallLog::query()->create([
            'reservation_id' => $reservation->id,
            'business_id' => $reservation->business_id,
            'to_phone' => $reservation->customer->phone,
            'attempt_number' => $attemptNumber,
            'status' => VoiceCallStatusEnum::Initiated,
            'cost_cents' => $costCents,
        ]);

        $baseUrl = rtrim((string) config('app.url'), '/');

        $response = Http::asForm()
            ->withBasicAuth((string) config('services.twilio.sid'), (string) config('services.twilio.token'))
            ->post(sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Calls.json', (string) config('services.twilio.sid')), [
                'To' => $reservation->customer->phone,
                'From' => (string) config('services.twilio.voice_number'),
                'Url' => "{$baseUrl}/api/v1/webhooks/voice/twiml/{$log->id}",
                'StatusCallback' => "{$baseUrl}/api/v1/webhooks/voice/status/{$log->id}",
                'StatusCallbackMethod' => 'POST',
            ])
            ->throw();

        $log->forceFill([
            'twilio_call_sid' => (string) $response->json('sid'),
        ])->save();

        $this->credits->deduct($reservation->business, $costCents);

        return $log->fresh();
    }
}

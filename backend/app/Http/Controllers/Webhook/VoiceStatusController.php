<?php

namespace App\Http\Controllers\Webhook;

use App\Enums\VoiceCallStatusEnum;
use App\Http\Controllers\Controller;
use App\Jobs\PlaceVoiceCallJob;
use App\Models\Business;
use App\Models\VoiceCallLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VoiceStatusController extends Controller
{
    public function status(Request $request, VoiceCallLog $voiceCallLog): Response
    {
        $voiceCallLog->loadMissing(['business', 'reservation']);
        /** @var Business $business */
        $business = $voiceCallLog->business;

        $status = match ($request->string('CallStatus')->toString()) {
            'ringing', 'in-progress', 'queued' => VoiceCallStatusEnum::Ringing,
            'completed' => VoiceCallStatusEnum::Answered,
            'busy', 'no-answer' => VoiceCallStatusEnum::NoAnswer,
            default => VoiceCallStatusEnum::Failed,
        };

        $voiceCallLog->update([
            'status' => $status,
            'duration_seconds' => $request->integer('CallDuration') ?: $voiceCallLog->duration_seconds,
        ]);

        if (
            $status === VoiceCallStatusEnum::NoAnswer
            && $voiceCallLog->attempt_number < $business->voice_retry_count
        ) {
            PlaceVoiceCallJob::dispatch(
                $voiceCallLog->reservation_id,
                $voiceCallLog->attempt_number + 1,
            )->delay(now()->addMinutes($business->voice_retry_delay_minutes));
        }

        return response('', 200);
    }
}

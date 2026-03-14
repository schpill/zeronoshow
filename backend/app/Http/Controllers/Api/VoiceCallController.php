<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VoiceCallLogResource;
use App\Jobs\PlaceVoiceCallJob;
use App\Models\Reservation;
use App\Models\VoiceCallLog;
use App\Services\VoiceCallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoiceCallController extends Controller
{
    public function queue(Request $request, Reservation $reservation): JsonResponse
    {
        abort_unless($reservation->business_id === $request->user()->id, 404);

        PlaceVoiceCallJob::dispatch($reservation->id, 1);

        return response()->json([
            'queued' => true,
        ]);
    }

    public function initiate(Request $request, Reservation $reservation, VoiceCallService $voiceCallService): JsonResponse
    {
        abort_unless($reservation->business_id === $request->user()->id, 404);

        $log = $voiceCallService->initiateCall($reservation->loadMissing(['business', 'customer']));

        return response()->json([
            'data' => (new VoiceCallLogResource($log))->resolve(),
        ], 202);
    }

    public function logs(Request $request, Reservation $reservation): JsonResponse
    {
        abort_unless($reservation->business_id === $request->user()->id, 404);

        $logs = VoiceCallLog::query()
            ->forReservation($reservation->id)
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => VoiceCallLogResource::collection($logs)->resolve(),
        ]);
    }
}

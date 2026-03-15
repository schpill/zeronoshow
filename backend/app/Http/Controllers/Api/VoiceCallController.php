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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Voice', description: 'Voice call endpoints')]
class VoiceCallController extends Controller
{
    #[OA\Post(
        path: '/api/v1/reservations/{reservation}/voice-call',
        tags: ['Voice'],
        summary: 'Queue a voice call',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'reservation', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [new OA\Response(response: 200, description: 'Voice call queued')],
    )]
    public function queue(Request $request, Reservation $reservation): JsonResponse
    {
        abort_unless($reservation->business_id === $request->user()->id, 404);

        PlaceVoiceCallJob::dispatch($reservation->id, 1);

        return response()->json([
            'queued' => true,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/reservations/{reservation}/call',
        tags: ['Voice'],
        summary: 'Initiate a voice call immediately',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'reservation', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [new OA\Response(response: 202, description: 'Voice call initiated')],
    )]
    public function initiate(Request $request, Reservation $reservation, VoiceCallService $voiceCallService): JsonResponse
    {
        abort_unless($reservation->business_id === $request->user()->id, 404);

        $log = $voiceCallService->initiateCall($reservation->loadMissing(['business', 'customer']));

        return response()->json([
            'data' => (new VoiceCallLogResource($log))->resolve(),
        ], 202);
    }

    #[OA\Get(
        path: '/api/v1/reservations/{reservation}/calls',
        tags: ['Voice'],
        summary: 'List voice call logs',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'reservation', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [new OA\Response(response: 200, description: 'Voice call logs')],
    )]
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeoChannelRequest;
use App\Http\Requests\UpdateLeoChannelRequest;
use App\Http\Resources\LeoChannelResource;
use App\Models\LeoChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeoChannelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $channel = LeoChannel::query()
            ->where('business_id', $request->user()->id)
            ->first();

        return response()->json([
            'channel' => $channel ? LeoChannelResource::make($channel)->resolve() : null,
        ]);
    }

    public function store(StoreLeoChannelRequest $request): JsonResponse
    {
        $business = $request->user();

        if (LeoChannel::query()->where('business_id', $business->id)->exists()) {
            return response()->json([
                'message' => 'Un canal Léo existe deja pour cet etablissement.',
            ], 409);
        }

        if ($request->string('channel')->toString() === 'whatsapp') {
            if ($business->whatsapp_monthly_cap_cents <= 0) {
                return response()->json([
                    'message' => 'Veuillez définir un budget mensuel WhatsApp avant de créer ce canal.',
                ], 422);
            }
        }

        if ($request->string('channel')->toString() === 'voice') {
            if ($business->voice_monthly_cap_cents <= 0) {
                return response()->json([
                    'message' => 'Veuillez définir un budget mensuel Appels avant de créer ce canal.',
                ], 422);
            }
        }

        $channel = LeoChannel::query()->create([
            'business_id' => $business->id,
            'channel' => $request->string('channel')->toString(),
            'external_identifier' => $request->string('external_identifier')->toString(),
            'bot_name' => $request->string('bot_name')->toString() ?: (string) config('leo.default_bot_name', 'Léo'),
            'is_active' => true,
        ]);

        return response()->json([
            'channel' => LeoChannelResource::make($channel)->resolve(),
        ], 201);
    }

    public function update(UpdateLeoChannelRequest $request, LeoChannel $leoChannel): JsonResponse
    {
        abort_unless($leoChannel->business_id === $request->user()->id, 404);

        $payload = [];

        if ($request->has('bot_name')) {
            $payload['bot_name'] = $request->string('bot_name')->toString() ?: (string) config('leo.default_bot_name', 'Léo');
        }

        if ($request->has('is_active')) {
            $payload['is_active'] = $request->boolean('is_active');
        }

        $leoChannel->fill($payload)->save();

        return response()->json([
            'channel' => LeoChannelResource::make($leoChannel->fresh())->resolve(),
        ]);
    }

    public function destroy(Request $request, LeoChannel $leoChannel): JsonResponse
    {
        abort_unless($leoChannel->business_id === $request->user()->id, 404);

        $leoChannel->delete();

        return response()->json(null, 204);
    }
}

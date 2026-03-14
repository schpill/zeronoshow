<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VoiceSettingsRequest;
use App\Http\Resources\VoiceCreditResource;
use App\Models\Business;
use Illuminate\Http\Request;

class VoiceSettingsController extends Controller
{
    public function show(Request $request): VoiceCreditResource
    {
        /** @var Business $business */
        $business = $request->user();

        return new VoiceCreditResource($business->load('leoChannel'));
    }

    public function update(VoiceSettingsRequest $request): VoiceCreditResource
    {
        /** @var Business $business */
        $business = $request->user();

        $business->update([
            'voice_auto_call_enabled' => $request->boolean('auto_call_enabled'),
            'voice_auto_call_score_threshold' => $request->integer('score_threshold') ?: null,
            'voice_auto_call_min_party_size' => $request->integer('min_party_size') ?: null,
            'voice_retry_count' => $request->integer('retry_count'),
            'voice_retry_delay_minutes' => $request->integer('retry_delay_minutes'),
        ]);

        return new VoiceCreditResource($business->load('leoChannel'));
    }
}

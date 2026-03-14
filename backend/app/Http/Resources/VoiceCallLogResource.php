<?php

namespace App\Http\Resources;

use App\Models\VoiceCallLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VoiceCallLog
 */
class VoiceCallLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reservation_id' => $this->reservation_id,
            'attempt_number' => $this->attempt_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'dtmf_response' => $this->dtmf_response,
            'duration_seconds' => $this->duration_seconds,
            'cost_cents' => $this->cost_cents,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

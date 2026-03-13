<?php

namespace App\Http\Resources;

use App\Models\SmsLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmsLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SmsLog $smsLog */
        $smsLog = $this->resource;

        return [
            'id' => $smsLog->id,
            'type' => $smsLog->type,
            'status' => $smsLog->status,
            'phone' => $smsLog->phone,
            'body' => $smsLog->body,
            'twilio_sid' => $smsLog->twilio_sid,
            'cost_eur' => $smsLog->cost_eur,
            'error_message' => $smsLog->error_message,
            'queued_at' => optional($smsLog->queued_at)->toIso8601String(),
            'sent_at' => optional($smsLog->sent_at)->toIso8601String(),
            'delivered_at' => optional($smsLog->delivered_at)->toIso8601String(),
        ];
    }
}

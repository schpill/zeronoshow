<?php

namespace App\Http\Resources;

use App\Models\Business;
use App\Models\LeoChannel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Business
 */
class VoiceCreditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'balance_cents' => $this->voice_credit_cents,
            'balance_euros' => round($this->voice_credit_cents / 100, 2),
            'monthly_cap_cents' => $this->voice_monthly_cap_cents,
            'monthly_cap_euros' => round($this->voice_monthly_cap_cents / 100, 2),
            'auto_renew' => $this->voice_auto_renew,
            'auto_call_enabled' => $this->voice_auto_call_enabled,
            'auto_call_score_threshold' => $this->voice_auto_call_score_threshold,
            'auto_call_min_party_size' => $this->voice_auto_call_min_party_size,
            'retry_count' => $this->voice_retry_count,
            'retry_delay_minutes' => $this->voice_retry_delay_minutes,
            'is_channel_active' => (bool) ($this->leoChannel instanceof LeoChannel ? $this->leoChannel->is_active : false),
            'low_balance_warning' => $this->voice_credit_cents < config('leo.voice.low_balance_threshold_cents'),
        ];
    }
}

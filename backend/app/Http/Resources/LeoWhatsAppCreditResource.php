<?php

namespace App\Http\Resources;

use App\Models\Business;
use App\Models\LeoChannel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Business
 */
class LeoWhatsAppCreditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'balance_cents' => $this->whatsapp_credit_cents,
            'balance_euros' => round($this->whatsapp_credit_cents / 100, 2),
            'monthly_cap_cents' => $this->whatsapp_monthly_cap_cents,
            'monthly_cap_euros' => round($this->whatsapp_monthly_cap_cents / 100, 2),
            'auto_renew' => $this->whatsapp_auto_renew,
            'is_channel_active' => (bool) ($this->leoChannel instanceof LeoChannel ? $this->leoChannel->is_active : false),
            'low_balance_warning' => $this->whatsapp_credit_cents < config('leo.whatsapp.low_balance_threshold_cents'),
        ];
    }
}

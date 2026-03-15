<?php

namespace App\Http\Resources;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Business $this */
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'subscription_status' => $this->subscription_status,
            'trial_ends_at' => optional($this->trial_ends_at)->toIso8601String(),
            'leo_addon_active' => (bool) $this->leo_addon_active,
            'onboarding_completed_at' => optional($this->onboarding_completed_at)->toIso8601String(),
        ];
    }
}

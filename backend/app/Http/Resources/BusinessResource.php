<?php

namespace App\Http\Resources;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Business $business */
        $business = $this->resource;

        return [
            'id' => $business->id,
            'name' => $business->name,
            'email' => $business->email,
            'phone' => $business->phone,
            'subscription_status' => $business->subscription_status,
            'trial_ends_at' => optional($business->trial_ends_at)->toIso8601String(),
            'leo_addon_active' => (bool) $business->leo_addon_active,
            'onboarding_completed_at' => optional($business->onboarding_completed_at)->toIso8601String(),
        ];
    }
}

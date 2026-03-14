<?php

namespace App\Http\Resources;

use App\Models\Business;
use App\Models\WidgetSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WidgetSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WidgetSetting $setting */
        $setting = $this->resource;

        /** @var Business|null $business */
        $business = $setting->business;

        $baseUrl = config('app.url');

        return [
            'id' => $setting->id,
            'business_id' => $setting->business_id,
            'logo_url' => $setting->logo_url,
            'accent_colour' => $setting->accent_colour,
            'max_party_size' => $setting->max_party_size,
            'advance_booking_days' => $setting->advance_booking_days,
            'same_day_cutoff_minutes' => $setting->same_day_cutoff_minutes,
            'is_enabled' => $setting->is_enabled,
            'embed_url' => $business !== null
                ? $baseUrl.'/widget/'.$business->public_token
                : null,
            'booking_url' => $business !== null
                ? $baseUrl.'/widget/'.$business->public_token
                : null,
            'created_at' => optional($setting->created_at)->toIso8601String(),
            'updated_at' => optional($setting->updated_at)->toIso8601String(),
        ];
    }
}

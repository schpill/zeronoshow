<?php

namespace App\Http\Resources;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Business $business */
        $business = $this->resource;

        return [
            'review_requests_enabled' => $business->review_requests_enabled,
            'review_platform' => $business->review_platform,
            'review_delay_hours' => $business->review_delay_hours,
            'google_place_id' => $business->google_place_id,
            'tripadvisor_location_id' => $business->tripadvisor_location_id,
        ];
    }
}

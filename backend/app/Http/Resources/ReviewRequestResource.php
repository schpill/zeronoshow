<?php

namespace App\Http\Resources;

use App\Enums\ReviewPlatformEnum;
use App\Enums\ReviewRequestStatusEnum;
use App\Models\ReviewRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ReviewRequest $reviewRequest */
        $reviewRequest = $this->resource;
        $reservation = $reviewRequest->reservation;

        return [
            'id' => $reviewRequest->id,
            'reservation_id' => $reviewRequest->reservation_id,
            'customer_name' => $reservation?->customer_name,
            'platform' => $reviewRequest->platform instanceof ReviewPlatformEnum
                ? $reviewRequest->platform->value
                : $reviewRequest->getRawOriginal('platform'),
            'status' => $reviewRequest->status instanceof ReviewRequestStatusEnum
                ? $reviewRequest->status->value
                : $reviewRequest->getRawOriginal('status'),
            'short_url' => url('/r/'.$reviewRequest->short_code),
            'sent_at' => optional($reviewRequest->sent_at)->toIso8601String(),
            'clicked_at' => optional($reviewRequest->clicked_at)->toIso8601String(),
            'expires_at' => optional($reviewRequest->expires_at)->toIso8601String(),
        ];
    }
}

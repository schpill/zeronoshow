<?php

namespace App\Services;

use App\Jobs\SendReviewRequestJob;
use App\Models\Reservation;
use App\Models\ReviewRequest;

class ReviewRequestService
{
    public function __construct(
        private readonly ReviewLinkService $reviewLinkService,
    ) {}

    public function createAndSend(Reservation $reservation): ?ReviewRequest
    {
        $reservation->loadMissing(['business', 'customer']);
        $business = $reservation->business;

        if (! $business->review_requests_enabled) {
            return null;
        }

        if (ReviewRequest::query()->where('reservation_id', $reservation->id)->exists()) {
            return null;
        }

        $reviewUrl = $business->review_platform === 'tripadvisor'
            ? $this->reviewLinkService->buildTripadvisorUrl((string) $business->tripadvisor_location_id)
            : $this->reviewLinkService->buildGoogleReviewUrl((string) $business->google_place_id);

        $reviewRequest = ReviewRequest::query()->create([
            'reservation_id' => $reservation->id,
            'business_id' => $reservation->business_id,
            'customer_id' => $reservation->customer_id,
            'channel' => 'sms',
            'platform' => $business->review_platform,
            'review_url' => $reviewUrl,
            'short_code' => $this->reviewLinkService->generateShortCode(),
            'status' => 'pending',
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
        ]);

        SendReviewRequestJob::dispatch($reviewRequest->id)
            ->delay(now()->addHours((int) $business->review_delay_hours));

        return $reviewRequest;
    }
}

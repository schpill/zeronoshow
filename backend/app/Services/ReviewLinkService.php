<?php

namespace App\Services;

use App\Models\ReviewRequest;
use Illuminate\Support\Str;

class ReviewLinkService
{
    public function buildGoogleReviewUrl(string $placeId): string
    {
        return 'https://search.google.com/local/writereview?placeid='.$placeId;
    }

    public function buildTripadvisorUrl(string $locationId): string
    {
        return 'https://www.tripadvisor.fr/UserReviewEdit-'.$locationId;
    }

    public function generateShortCode(): string
    {
        do {
            $code = Str::lower(Str::random(8));
        } while (ReviewRequest::query()->where('short_code', $code)->exists());

        return $code;
    }
}

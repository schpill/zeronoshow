<?php

namespace App\Http\Controllers\Public;

use App\Enums\ReviewRequestStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\ReviewRequest;
use Illuminate\Http\RedirectResponse;

class ReviewRedirectController extends Controller
{
    public function redirect(string $shortCode): RedirectResponse
    {
        $reviewRequest = ReviewRequest::query()
            ->where('short_code', $shortCode)
            ->whereIn('status', [
                ReviewRequestStatusEnum::Sent->value,
                ReviewRequestStatusEnum::Clicked->value,
            ])
            ->where('expires_at', '>', now())
            ->first();

        abort_if(! $reviewRequest, 404);

        $reviewRequest->update([
            'status' => ReviewRequestStatusEnum::Clicked->value,
            'clicked_at' => now(),
        ]);

        return redirect()->away($reviewRequest->review_url);
    }
}

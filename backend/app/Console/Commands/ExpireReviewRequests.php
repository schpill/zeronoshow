<?php

namespace App\Console\Commands;

use App\Enums\ReviewRequestStatusEnum;
use App\Models\ReviewRequest;
use Illuminate\Console\Command;

class ExpireReviewRequests extends Command
{
    protected $signature = 'review-requests:expire';

    protected $description = 'Expire review requests older than their validity window';

    public function handle(): int
    {
        $count = ReviewRequest::query()
            ->where('status', ReviewRequestStatusEnum::Sent->value)
            ->where('expires_at', '<', now())
            ->update([
                'status' => ReviewRequestStatusEnum::Expired->value,
            ]);

        $this->info("Expired {$count} review requests.");

        return self::SUCCESS;
    }
}

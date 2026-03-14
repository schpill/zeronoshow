<?php

namespace App\Jobs;

use App\Enums\ReviewRequestStatusEnum;
use App\Models\ReviewRequest;
use App\Models\SmsLog;
use App\Services\Contracts\SmsServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Sentry\Laravel\Facade;

class SendReviewRequestJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public string $reviewRequestId,
    ) {}

    public function handle(SmsServiceInterface $sms): void
    {
        $reviewRequest = ReviewRequest::query()
            ->with(['reservation.business', 'reservation.customer'])
            ->find($this->reviewRequestId);

        if (! $reviewRequest || $reviewRequest->status !== ReviewRequestStatusEnum::Pending) {
            return;
        }

        if ($reviewRequest->reservation?->customer?->opted_out) {
            return;
        }

        $shortUrl = rtrim((string) config('app.url'), '/').'/r/'.$reviewRequest->short_code;
        $body = sprintf(
            'Bonjour %s, merci pour votre visite chez %s ! Votre avis compte beaucoup : %s',
            $reviewRequest->reservation->customer_name,
            $reviewRequest->reservation->business->name,
            $shortUrl,
        );

        $log = SmsLog::query()->create([
            'reservation_id' => $reviewRequest->reservation_id,
            'business_id' => $reviewRequest->business_id,
            'phone' => $reviewRequest->reservation->customer->phone,
            'type' => 'review_request',
            'body' => $body,
            'status' => 'queued',
            'queued_at' => now(),
            'created_at' => now(),
        ]);

        $sms->send($log);

        $reviewRequest->update([
            'status' => ReviewRequestStatusEnum::Sent->value,
            'sent_at' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        ReviewRequest::query()
            ->where('id', $this->reviewRequestId)
            ->update([
                'status' => ReviewRequestStatusEnum::Pending->value,
                'sent_at' => null,
            ]);

        SmsLog::query()
            ->where('reservation_id', ReviewRequest::query()->where('id', $this->reviewRequestId)->value('reservation_id'))
            ->latest('created_at')
            ->first()
            ?->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

        Log::error('Review request SMS failed', [
            'review_request_id' => $this->reviewRequestId,
            'error' => $exception->getMessage(),
        ]);

        if (class_exists(Facade::class)) {
            Facade::captureException($exception);

            return;
        }

        report($exception);
    }
}

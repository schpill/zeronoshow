<?php

namespace App\Jobs;

use App\Models\Business;
use App\Services\Contracts\SmsServiceInterface;
use App\Services\WaitlistService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyWaitlistJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $businessId,
        public string $slotDate,
        public string $slotTime
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WaitlistService $waitlistService, SmsServiceInterface $smsService): void
    {
        $entry = $waitlistService->notifyNext($this->businessId, $this->slotDate, $this->slotTime);

        if (! $entry) {
            Log::info("No pending waitlist entries for business {$this->businessId} on {$this->slotDate} at {$this->slotTime}");

            return;
        }

        $business = Business::findOrFail($this->businessId);
        $confirmUrl = config('app.url')."/waitlist/confirm/{$entry->confirmation_token}";
        $window = $business->waitlist_notification_window_minutes;

        $message = "Une table est disponible chez {$business->name} le {$entry->slot_date->format('d/m')} à ".substr($entry->slot_time, 0, 5).'. ';
        $message .= "Confirmez dans {$window} minutes ici : {$confirmUrl}";

        try {
            $smsService->sendSms($entry->client_phone, $message);
            Log::info("Waitlist notification sent to client for business {$this->businessId}");
        } catch (\Exception $e) {
            Log::error('Failed to send waitlist SMS: '.$e->getMessage());
            throw $e;
        }
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }
}

<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Models\SmsLog;
use App\Services\Contracts\SmsServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendVerificationSms implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    /**
     * Create a new job instance.
     */
    public function __construct(public string $reservationId) {}

    /**
     * Execute the job.
     */
    public function handle(SmsServiceInterface $sms): void
    {
        $reservation = Reservation::query()
            ->with(['customer', 'business'])
            ->find($this->reservationId);

        if (! $reservation || $reservation->status !== 'pending_verification' || ! $reservation->confirmation_token) {
            return;
        }

        $confirmUrl = route('confirmation.show', $reservation->confirmation_token);
        $cancelUrl = route('confirmation.confirm', $reservation->confirmation_token);

        $body = sprintf(
            'Bonjour %s, confirmez votre RDV le %s à %s. Confirmez : %s | Annulez : %s',
            $reservation->customer_name,
            $reservation->scheduled_at->timezone($reservation->business->timezone)->format('d/m/Y'),
            $reservation->scheduled_at->timezone($reservation->business->timezone)->format('H:i'),
            $confirmUrl,
            $cancelUrl,
        );

        $log = SmsLog::query()->create([
            'reservation_id' => $reservation->id,
            'business_id' => $reservation->business_id,
            'phone' => $reservation->customer->phone,
            'type' => 'verification',
            'body' => $body,
            'status' => 'queued',
            'queued_at' => now(),
            'created_at' => now(),
        ]);

        $response = $sms->send($reservation->customer->phone, $body);

        $log->update([
            'twilio_sid' => $response['sid'],
            'status' => $response['status'],
            'sent_at' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        SmsLog::query()
            ->where('reservation_id', $this->reservationId)
            ->latest('created_at')
            ->first()
            ?->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

        Log::error('Verification SMS failed', [
            'reservation_id' => $this->reservationId,
            'error' => $exception->getMessage(),
        ]);
    }
}

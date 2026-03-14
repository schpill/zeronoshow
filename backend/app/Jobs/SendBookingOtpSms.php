<?php

namespace App\Jobs;

use App\Models\SmsLog;
use App\Services\Contracts\SmsServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Sentry\Laravel\Facade;

class SendBookingOtpSms implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public int $timeout = 30;

    public function __construct(
        public string $phone,
        public string $code,
    ) {}

    public function handle(SmsServiceInterface $sms): void
    {
        $body = sprintf(
            'Votre code de réservation ZeroNoShow : %s. Valide 10 minutes.',
            $this->code,
        );

        $log = SmsLog::query()->create([
            'reservation_id' => null,
            'business_id' => null,
            'phone' => $this->phone,
            'type' => 'booking_otp',
            'body' => $body,
            'status' => 'queued',
            'queued_at' => now(),
            'created_at' => now(),
        ]);

        $sms->send($log);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Booking OTP SMS failed', [
            'phone' => $this->phone,
            'error' => $exception->getMessage(),
        ]);

        if (class_exists(Facade::class)) {
            Facade::captureException($exception);

            return;
        }

        report($exception);
    }
}

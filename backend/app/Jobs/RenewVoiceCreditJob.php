<?php

namespace App\Jobs;

use App\Mail\VoiceCreditRenewedMail;
use App\Models\Business;
use App\Services\StripeService;
use App\Services\VoiceCreditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RenewVoiceCreditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 600];

    public function __construct(
        public readonly Business $business,
    ) {}

    public function handle(StripeService $stripe, VoiceCreditService $voiceCredits): void
    {
        if (! $this->business->voice_auto_renew || $this->business->voice_monthly_cap_cents <= 0) {
            return;
        }

        if ($this->business->voice_last_renewed_at && $this->business->voice_last_renewed_at->isCurrentMonth()) {
            return;
        }

        try {
            $stripe->createVoiceInvoiceItem($this->business, $this->business->voice_monthly_cap_cents);
            $stripe->finalizeAndPayInvoice($this->business);

            $voiceCredits->topUp($this->business, $this->business->voice_monthly_cap_cents);

            $this->business->update([
                'voice_last_renewed_at' => now(),
            ]);

            Mail::to($this->business->email)->send(
                new VoiceCreditRenewedMail(
                    $this->business,
                    $this->business->voice_monthly_cap_cents,
                    $this->business->fresh()->voice_credit_cents
                )
            );

            Log::info('Voice monthly credit renewed.', [
                'business_id' => $this->business->id,
                'amount_cents' => $this->business->voice_monthly_cap_cents,
            ]);
        } catch (Throwable $exception) {
            Log::error('Voice monthly credit renewal failed.', [
                'business_id' => $this->business->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}

<?php

namespace App\Jobs;

use App\Mail\WhatsAppCreditRenewedMail;
use App\Models\Business;
use App\Services\Leo\LeoWhatsAppCreditService;
use App\Services\StripeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RenewWhatsAppCreditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 300, 600];

    public function __construct(
        public readonly Business $business,
    ) {}

    public function handle(StripeService $stripe, LeoWhatsAppCreditService $waCredits): void
    {
        if (! $this->business->whatsapp_auto_renew || $this->business->whatsapp_monthly_cap_cents <= 0) {
            return;
        }

        // Idempotency check: already renewed this month?
        if ($this->business->whatsapp_last_renewed_at && $this->business->whatsapp_last_renewed_at->isCurrentMonth()) {
            return;
        }

        try {
            $stripe->createWhatsAppInvoiceItem($this->business, $this->business->whatsapp_monthly_cap_cents);
            $stripe->finalizeAndPayInvoice($this->business);

            $waCredits->topUp($this->business, $this->business->whatsapp_monthly_cap_cents);

            $this->business->update([
                'whatsapp_last_renewed_at' => now(),
            ]);

            Mail::to($this->business->email)->send(
                new WhatsAppCreditRenewedMail(
                    $this->business,
                    $this->business->whatsapp_monthly_cap_cents,
                    $this->business->whatsapp_credit_cents
                )
            );

            Log::info('WhatsApp monthly credit renewed.', [
                'business_id' => $this->business->id,
                'amount_cents' => $this->business->whatsapp_monthly_cap_cents,
            ]);

            // TODO: Dispatch WhatsAppCreditRenewedMail
        } catch (Throwable $e) {
            Log::error('WhatsApp monthly credit renewal failed.', [
                'business_id' => $this->business->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

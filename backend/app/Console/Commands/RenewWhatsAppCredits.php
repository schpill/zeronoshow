<?php

namespace App\Console\Commands;

use App\Jobs\RenewWhatsAppCreditJob;
use App\Models\Business;
use Illuminate\Console\Command;

class RenewWhatsAppCredits extends Command
{
    protected $signature = 'leo:renew-whatsapp-credits';

    protected $description = 'Dispatches renewal jobs for businesses with WhatsApp auto-renew enabled.';

    public function handle(): int
    {
        $businesses = Business::query()
            ->where('whatsapp_auto_renew', true)
            ->where('whatsapp_monthly_cap_cents', '>', 0)
            ->where(function ($query) {
                $query->whereNull('whatsapp_last_renewed_at')
                    ->orWhere('whatsapp_last_renewed_at', '<', now()->startOfMonth());
            })
            ->get();

        $count = $businesses->count();
        $this->info("Found $count eligible businesses for WhatsApp credit renewal.");

        foreach ($businesses as $business) {
            RenewWhatsAppCreditJob::dispatch($business);
        }

        $this->info("Dispatched $count jobs.");

        return Command::SUCCESS;
    }
}

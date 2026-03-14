<?php

namespace App\Console\Commands;

use App\Services\Leo\LeoWhatsAppConversationTracker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PurgeWhatsAppConversationWindows extends Command
{
    protected $signature = 'whatsapp:purge-windows';

    protected $description = 'Deletes expired WhatsApp conversation windows.';

    public function handle(LeoWhatsAppConversationTracker $tracker): int
    {
        $count = $tracker->purgeExpired();

        if ($count > 0) {
            $this->info("Purged $count expired WhatsApp windows.");
            Log::info('WhatsApp conversation windows purged.', ['count' => $count]);
        }

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Jobs\RenewVoiceCreditJob;
use App\Models\Business;
use Illuminate\Console\Command;

class RenewVoiceCredits extends Command
{
    protected $signature = 'leo:renew-voice-credits';

    protected $description = 'Queue the monthly voice credit renewal jobs.';

    public function handle(): int
    {
        $count = 0;

        Business::query()
            ->where('voice_auto_renew', true)
            ->where('voice_monthly_cap_cents', '>', 0)
            ->orderBy('name')
            ->get()
            ->each(function (Business $business) use (&$count): void {
                RenewVoiceCreditJob::dispatch($business);
                $count++;
            });

        $this->info("Queued {$count} voice credit renewal jobs.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\SmsLog;
use Illuminate\Console\Command;

class PurgeSmsLogs extends Command
{
    protected $signature = 'sms-logs:purge {--dry-run : Count records without deleting them}';

    protected $description = 'Delete sms_logs records older than 90 days';

    public function handle(): int
    {
        $threshold = now()->subDays(90);
        $query = SmsLog::query()->where('created_at', '<', $threshold);
        $count = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info(sprintf('%d sms logs older than 90 days would be deleted', $count));

            return self::SUCCESS;
        }

        $query->delete();

        $this->info(sprintf('Deleted %d sms logs older than 90 days', $count));

        return self::SUCCESS;
    }
}

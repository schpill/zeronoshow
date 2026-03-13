<?php

namespace App\Console\Commands;

use App\Models\LeoMessageLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PurgeLeoMessageLogs extends Command
{
    protected $signature = 'leo-logs:purge {--days=90 : Nombre de jours a conserver}';

    protected $description = 'Delete leo_message_logs records older than the configured retention window';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $threshold = now()->subDays($days);
        $query = LeoMessageLog::query()->where('created_at', '<', $threshold);
        $count = (clone $query)->count();

        $query->delete();

        Log::info('Purged Leo message logs.', [
            'days' => $days,
            'count' => $count,
        ]);

        $this->info(sprintf('Deleted %d Leo message logs older than %d days', $count, $days));

        return self::SUCCESS;
    }
}

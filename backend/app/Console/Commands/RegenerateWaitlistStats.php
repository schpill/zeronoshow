<?php

namespace App\Console\Commands;

use App\Models\WaitlistEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RegenerateWaitlistStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waitlist:stats {--business= : Filter by business UUID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Displays current statistics for the waitlist';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $businessId = $this->option('business');

        $query = WaitlistEntry::query();

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $stats = $query->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        $this->table(
            ['Status', 'Count'],
            collect($stats)->map(fn ($count, $status) => [$status, $count])->toArray()
        );

        $totalEntries = array_sum($stats);
        $this->info("Total waitlist entries: {$totalEntries}");

        return 0;
    }
}

<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RecalculateReliabilityScore implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $customerId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Score recalculation queued for {$this->customerId}");
    }
}

<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Reservation;
use App\Services\ReliabilityScoreService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RecalculateReliabilityScore implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    /**
     * Create a new job instance.
     */
    public function __construct(public string $customerId) {}

    /**
     * Execute the job.
     */
    public function handle(ReliabilityScoreService $service): void
    {
        $customer = Customer::query()->find($this->customerId);

        if (! $customer) {
            return;
        }

        $customer = $service->recalculate($customer);

        $businessIds = Reservation::query()
            ->where('customer_id', $customer->id)
            ->distinct()
            ->pluck('business_id');

        foreach ($businessIds as $businessId) {
            Cache::forget("dashboard:{$businessId}:today:list");
        }

        if ($businessIds->isNotEmpty() && config('cache.default') !== 'redis') {
            Cache::flush();
        }
    }
}

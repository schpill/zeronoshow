<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Reservation;
use App\Services\ReliabilityScoreService;
use Carbon\Carbon;
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

        $reservations = Reservation::query()
            ->with('business')
            ->where('customer_id', $customer->id)
            ->get();

        foreach ($reservations as $reservation) {
            $businessId = $reservation->business_id;
            $timezone = $reservation->business?->timezone ?? 'UTC';
            $scheduledAt = Carbon::parse($reservation->scheduled_at)->timezone($timezone);
            $dateKey = $scheduledAt->toDateString();
            $weekKey = sprintf('%d-W%02d', $scheduledAt->isoWeekYear, $scheduledAt->isoWeek);

            Cache::forget("dashboard:{$businessId}:none:none");
            Cache::forget("dashboard:{$businessId}:{$dateKey}:none");
            Cache::forget("dashboard:{$businessId}:none:{$weekKey}");
            Cache::forget("dashboard:{$businessId}:today:list");
        }
    }
}

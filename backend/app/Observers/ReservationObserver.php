<?php

namespace App\Observers;

use App\Enums\WaitlistStatusEnum;
use App\Jobs\NotifyWaitlistJob;
use App\Jobs\RecalculateReliabilityScore;
use App\Jobs\SendLeoNotificationJob;
use App\Models\Business;
use App\Models\LeoChannel;
use App\Models\Reservation;
use App\Models\WaitlistEntry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReservationObserver
{
    private const TERMINAL_STATUSES = [
        'confirmed',
        'cancelled_by_client',
        'cancelled_no_confirmation',
        'no_show',
        'show',
    ];

    public function updated(Reservation $reservation): void
    {
        $this->bumpDashboardVersion($reservation);

        if (! $reservation->wasChanged('status')) {
            return;
        }

        $previousStatus = $reservation->getOriginal('status');
        $status = $reservation->status;
        $showDelta = $this->counterContribution($status, 'show') - $this->counterContribution($previousStatus, 'show');
        $noShowDelta = $this->counterContribution($status, 'no_show') - $this->counterContribution($previousStatus, 'no_show');

        if (
            ! in_array($status, self::TERMINAL_STATUSES, true)
            && ! in_array($previousStatus, self::TERMINAL_STATUSES, true)
        ) {
            return;
        }

        if ($showDelta !== 0 || $noShowDelta !== 0) {
            DB::table('customers')
                ->where('id', $reservation->customer_id)
                ->update([
                    'shows_count' => DB::raw(sprintf(
                        'CASE WHEN shows_count + (%1$d) < 0 THEN 0 ELSE shows_count + (%1$d) END',
                        $showDelta,
                    )),
                    'no_shows_count' => DB::raw(sprintf(
                        'CASE WHEN no_shows_count + (%1$d) < 0 THEN 0 ELSE no_shows_count + (%1$d) END',
                        $noShowDelta,
                    )),
                ]);
        }

        RecalculateReliabilityScore::dispatch($reservation->customer_id);
        $this->dispatchLeoNotificationIfNeeded($reservation, $status);
        $this->triggerWaitlistIfNeeded($reservation, $status);
    }

    public function created(Reservation $reservation): void
    {
        $this->bumpDashboardVersion($reservation);
    }

    public function deleted(Reservation $reservation): void
    {
        $this->bumpDashboardVersion($reservation);
    }

    private function counterContribution(string $status, string $counter): int
    {
        return match ($counter) {
            'show' => $status === 'show' ? 1 : 0,
            'no_show' => $status === 'no_show' ? 1 : 0,
            default => 0,
        };
    }

    private function bumpDashboardVersion(Reservation $reservation): void
    {
        $key = "dashboard_version:{$reservation->business_id}";

        Cache::forever($key, ((int) Cache::get($key, 1)) + 1);
    }

    private function dispatchLeoNotificationIfNeeded(Reservation $reservation, string $status): void
    {
        if (! in_array($status, ['cancelled_by_client', 'no_show'], true)) {
            return;
        }

        /** @var Business|null $business */
        $business = $reservation->business()->first();

        if (! $business?->leo_addon_active) {
            return;
        }

        $hasActiveLeoChannel = LeoChannel::query()
            ->where('business_id', $reservation->business_id)
            ->where('is_active', true)
            ->exists();

        if (! $hasActiveLeoChannel) {
            return;
        }

        SendLeoNotificationJob::dispatch($reservation->id, $status);
    }

    private function triggerWaitlistIfNeeded(Reservation $reservation, string $status): void
    {
        if (! in_array($status, ['cancelled_by_client', 'cancelled_by_business', 'no_show'], true)) {
            return;
        }

        /** @var Business|null $business */
        $business = $reservation->business()->first();

        if (! $business?->waitlist_enabled) {
            return;
        }

        $hasWaitlist = \App\Models\WaitlistEntry::query()
            ->where('business_id', $reservation->business_id)
            ->whereDate('slot_date', $reservation->scheduled_at->format('Y-m-d'))
            ->whereTime('slot_time', $reservation->scheduled_at->format('H:i:00'))
            ->where('status', \App\Enums\WaitlistStatusEnum::Pending)
            ->exists();

        if (! $hasWaitlist) {
            return;
        }

        \App\Jobs\NotifyWaitlistJob::dispatch(
            $reservation->business_id,
            $reservation->scheduled_at->format('Y-m-d'),
            $reservation->scheduled_at->format('H:i:00')
        );

    }
}

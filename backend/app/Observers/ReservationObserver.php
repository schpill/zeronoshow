<?php

namespace App\Observers;

use App\Jobs\RecalculateReliabilityScore;
use App\Models\Reservation;
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
    }

    private function counterContribution(string $status, string $counter): int
    {
        return match ($counter) {
            'show' => in_array($status, ['show', 'confirmed'], true) ? 1 : 0,
            'no_show' => $status === 'no_show' ? 1 : 0,
            default => 0,
        };
    }
}

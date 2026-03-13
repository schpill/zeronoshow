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

        $status = $reservation->status;

        if (! in_array($status, self::TERMINAL_STATUSES, true)) {
            return;
        }

        if ($status === 'no_show') {
            DB::table('customers')
                ->where('id', $reservation->customer_id)
                ->increment('no_shows_count');
        }

        if (in_array($status, ['show', 'confirmed'], true)) {
            DB::table('customers')
                ->where('id', $reservation->customer_id)
                ->increment('shows_count');
        }

        RecalculateReliabilityScore::dispatch($reservation->customer_id);
    }
}

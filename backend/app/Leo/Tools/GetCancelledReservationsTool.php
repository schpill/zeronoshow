<?php

namespace App\Leo\Tools;

use App\Models\Reservation;

class GetCancelledReservationsTool
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(string $businessId): array
    {
        return Reservation::query()
            ->where('business_id', $businessId)
            ->whereIn('status', ['cancelled_by_client', 'cancelled_no_confirmation'])
            ->whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()])
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (Reservation $reservation): array => [
                'time' => $reservation->scheduled_at->format('H:i'),
                'name' => $reservation->customer_name,
                'guests' => $reservation->guests,
                'cancelled_at' => optional($reservation->status_changed_at)->format('H:i'),
            ])
            ->all();
    }
}

<?php

namespace App\Services\Leo;

use App\Models\Reservation;

class GetPendingReservationsTool
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(string $businessId): array
    {
        return Reservation::query()
            ->where('business_id', $businessId)
            ->whereIn('status', ['pending_verification', 'pending_reminder'])
            ->whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()])
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (Reservation $reservation): array => [
                'time' => $reservation->scheduled_at->format('H:i'),
                'name' => $reservation->customer_name,
                'guests' => $reservation->guests,
            ])
            ->all();
    }
}

<?php

namespace App\Leo\Tools;

use App\Models\Reservation;

class GetUpcomingReservationsTool
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(string $businessId, int $limit = 5): array
    {
        return Reservation::query()
            ->where('business_id', $businessId)
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get()
            ->map(fn (Reservation $reservation): array => [
                'time' => $reservation->scheduled_at->format('H:i'),
                'name' => $reservation->customer_name,
                'guests' => $reservation->guests,
            ])
            ->all();
    }
}

<?php

namespace App\Leo\Tools;

use App\Models\Reservation;

class GetReservationDetailsTool
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(string $businessId, string $query): array
    {
        $needle = mb_strtolower(trim($query));

        return Reservation::query()
            ->where('business_id', $businessId)
            ->whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()])
            ->get()
            ->filter(function (Reservation $reservation) use ($needle): bool {
                return str_contains(mb_strtolower($reservation->customer_name), $needle)
                    || $reservation->scheduled_at->format('H:i') === $needle;
            })
            ->values()
            ->map(fn (Reservation $reservation): array => [
                'name' => $reservation->customer_name,
                'time' => $reservation->scheduled_at->format('H:i'),
                'guests' => $reservation->guests,
                'status' => $reservation->status,
                'notes' => $reservation->notes,
            ])
            ->all();
    }
}

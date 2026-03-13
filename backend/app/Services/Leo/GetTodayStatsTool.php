<?php

namespace App\Services\Leo;

use App\Models\Reservation;

class GetTodayStatsTool
{
    /**
     * @return array<string, int|float>
     */
    public function execute(string $businessId): array
    {
        $reservations = Reservation::query()
            ->where('business_id', $businessId)
            ->whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()])
            ->get();

        return [
            'total' => $reservations->count(),
            'confirmed' => $reservations->where('status', 'confirmed')->count(),
            'pending' => $reservations->whereIn('status', ['pending_verification', 'pending_reminder'])->count(),
            'cancelled' => $reservations->whereIn('status', ['cancelled_by_client', 'cancelled_no_confirmation'])->count(),
            'no_show' => $reservations->where('status', 'no_show')->count(),
            'show' => $reservations->where('status', 'show')->count(),
        ];
    }
}

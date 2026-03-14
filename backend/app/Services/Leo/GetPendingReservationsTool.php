<?php

namespace App\Services\Leo;

use App\Enums\WaitlistStatusEnum;
use App\Models\Reservation;
use App\Models\WaitlistEntry;

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
                'waitlist_count' => WaitlistEntry::query()
                    ->where('business_id', $reservation->business_id)
                    ->whereDate('slot_date', $reservation->scheduled_at->format('Y-m-d'))
                    ->whereTime('slot_time', $reservation->scheduled_at->format('H:i:00'))
                    ->where('status', WaitlistStatusEnum::Pending)
                    ->count(),
            ])
            ->all();
    }
}

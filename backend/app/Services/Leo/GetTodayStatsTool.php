<?php

namespace App\Services\Leo;

use App\Models\Reservation;

class GetTodayStatsTool
{
    /**
     * @return array{total:int, confirmed:int, pending:int, cancelled:int, no_show:int, show:int, score_avg:float|null}
     */
    public function execute(string $businessId): array
    {
        $reservations = Reservation::query()
            ->where('business_id', $businessId)
            ->whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()])
            ->with('customer:id,reliability_score')
            ->get();

        $confirmedScores = $reservations
            ->where('status', 'confirmed')
            ->map(fn (Reservation $reservation): ?float => $reservation->customer->reliability_score)
            ->filter(fn (?float $score): bool => $score !== null);

        return [
            'total' => $reservations->count(),
            'confirmed' => $reservations->where('status', 'confirmed')->count(),
            'pending' => $reservations->whereIn('status', ['pending_verification', 'pending_reminder'])->count(),
            'cancelled' => $reservations->whereIn('status', ['cancelled_by_client', 'cancelled_no_confirmation'])->count(),
            'no_show' => $reservations->where('status', 'no_show')->count(),
            'show' => $reservations->where('status', 'show')->count(),
            'score_avg' => $confirmedScores->isEmpty() ? null : round((float) $confirmedScores->avg(), 2),
        ];
    }
}

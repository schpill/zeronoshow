<?php

namespace App\Services;

use App\Models\Customer;

class ReliabilityScoreService
{
    public static function getTierForScore(?float $score): string
    {
        if ($score === null || $score < 70) {
            return 'at_risk';
        }

        if ($score < 90) {
            return 'average';
        }

        return 'reliable';
    }

    public function recalculate(Customer $customer): Customer
    {
        $total = $customer->shows_count + $customer->no_shows_count;
        $score = $total === 0 ? null : round(($customer->shows_count / $total) * 100, 2);

        $customer->forceFill([
            'reliability_score' => $score,
            'score_tier' => self::getTierForScore($score),
            'last_calculated_at' => now(),
        ])->save();

        return $customer->fresh();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property float|null $reliability_score
 */
class Customer extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'phone',
        'reservations_count',
        'shows_count',
        'no_shows_count',
        'reliability_score',
        'score_tier',
        'opted_out',
        'opted_out_at',
        'last_calculated_at',
    ];

    protected $casts = [
        'reliability_score' => 'float',
        'opted_out' => 'boolean',
        'opted_out_at' => 'datetime',
        'last_calculated_at' => 'datetime',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function getScoreTier(): string
    {
        if ($this->score_tier !== null) {
            return $this->score_tier;
        }

        return \App\Services\ReliabilityScoreService::getTierForScore($this->reliability_score);
    }
}

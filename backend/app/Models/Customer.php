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
        'last_calculated_at',
    ];

    protected $casts = [
        'reliability_score' => 'float',
        'last_calculated_at' => 'datetime',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function getScoreTier(): string
    {
        if ($this->reliability_score === null) {
            return 'at_risk';
        }

        if ($this->reliability_score >= 90) {
            return 'reliable';
        }

        if ($this->reliability_score >= 70) {
            return 'average';
        }

        return 'at_risk';
    }
}

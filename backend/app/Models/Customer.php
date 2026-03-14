<?php

namespace App\Models;

use App\Services\ReliabilityScoreService;
use Illuminate\Database\Eloquent\Builder;
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
        'notes',
        'is_vip',
        'is_blacklisted',
        'birthday_month',
        'birthday_day',
        'preferred_table_notes',
    ];

    protected $casts = [
        'reliability_score' => 'float',
        'opted_out' => 'boolean',
        'opted_out_at' => 'datetime',
        'last_calculated_at' => 'datetime',
        'is_vip' => 'boolean',
        'is_blacklisted' => 'boolean',
        'birthday_month' => 'integer',
        'birthday_day' => 'integer',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function scopeVip(Builder $query): Builder
    {
        return $query->where('is_vip', true);
    }

    public function scopeBlacklisted(Builder $query): Builder
    {
        return $query->where('is_blacklisted', true);
    }

    public function scopeBirthdayThisMonth(Builder $query): Builder
    {
        return $query->where('birthday_month', now()->month);
    }

    public function getScoreTier(): string
    {
        if ($this->score_tier !== null) {
            return $this->score_tier;
        }

        return ReliabilityScoreService::getTierForScore($this->reliability_score);
    }
}

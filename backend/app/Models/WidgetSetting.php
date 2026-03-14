<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WidgetSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'business_id',
        'logo_url',
        'accent_colour',
        'max_party_size',
        'advance_booking_days',
        'same_day_cutoff_minutes',
        'is_enabled',
    ];

    protected $casts = [
        'max_party_size' => 'integer',
        'advance_booking_days' => 'integer',
        'same_day_cutoff_minutes' => 'integer',
        'is_enabled' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function publicConfig(): array
    {
        return [
            'logo_url' => $this->logo_url,
            'accent_colour' => $this->accent_colour,
            'max_party_size' => $this->max_party_size,
            'advance_booking_days' => $this->advance_booking_days,
            'same_day_cutoff_minutes' => $this->same_day_cutoff_minutes,
            'is_enabled' => $this->is_enabled,
        ];
    }
}

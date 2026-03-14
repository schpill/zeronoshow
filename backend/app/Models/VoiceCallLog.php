<?php

namespace App\Models;

use App\Enums\VoiceCallStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceCallLog extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'reservation_id',
        'business_id',
        'to_phone',
        'attempt_number',
        'status',
        'dtmf_response',
        'duration_seconds',
        'cost_cents',
        'twilio_call_sid',
    ];

    protected $casts = [
        'status' => VoiceCallStatusEnum::class,
        'attempt_number' => 'integer',
        'duration_seconds' => 'integer',
        'cost_cents' => 'integer',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeForReservation(Builder $query, string $reservationId): Builder
    {
        return $query->where('reservation_id', $reservationId);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [
            VoiceCallStatusEnum::Initiated->value,
            VoiceCallStatusEnum::Ringing->value,
        ]);
    }
}

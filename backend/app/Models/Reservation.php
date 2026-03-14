<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $scheduled_at
 * @property Carbon|null $token_expires_at
 * @property Customer $customer
 * @property Business $business
 */
class Reservation extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'business_id',
        'customer_id',
        'customer_name',
        'scheduled_at',
        'guests',
        'notes',
        'status',
        'phone_verified',
        'confirmation_token',
        'token_expires_at',
        'reminder_2h_sent',
        'reminder_30m_sent',
        'status_changed_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'phone_verified' => 'boolean',
        'token_expires_at' => 'datetime',
        'reminder_2h_sent' => 'boolean',
        'reminder_30m_sent' => 'boolean',
        'status_changed_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }

    public function voiceCallLogs(): HasMany
    {
        return $this->hasMany(VoiceCallLog::class);
    }

    public function reviewRequests(): HasMany
    {
        return $this->hasMany(ReviewRequest::class);
    }

    public function scopeNeedingReminder(Builder $query): Builder
    {
        return $query
            ->whereIn('status', ['pending_reminder', 'confirmed'])
            ->where('reminder_2h_sent', false)
            ->whereBetween('scheduled_at', [now()->addHour()->addMinutes(55), now()->addHours(2)->addMinutes(5)]);
    }
}

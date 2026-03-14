<?php

namespace App\Models;

use App\Enums\ChannelTypeEnum;
use App\Enums\WaitlistStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $business_id
 * @property Carbon $slot_date
 * @property string $slot_time
 * @property string $client_name
 * @property string $client_phone
 * @property int $party_size
 * @property int $priority_order
 * @property WaitlistStatusEnum $status
 * @property ChannelTypeEnum $channel
 * @property Carbon|null $notified_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $confirmed_at
 * @property string|null $confirmation_token
 * @property Business $business
 */
class WaitlistEntry extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'business_id',
        'slot_date',
        'slot_time',
        'client_name',
        'client_phone',
        'party_size',
        'priority_order',
        'status',
        'channel',
        'notified_at',
        'expires_at',
        'confirmed_at',
        'confirmation_token',
    ];

    protected $casts = [
        'slot_date' => 'date',
        'status' => WaitlistStatusEnum::class,
        'channel' => ChannelTypeEnum::class,
        'notified_at' => 'datetime',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'party_size' => 'integer',
        'priority_order' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', WaitlistStatusEnum::Pending);
    }

    public function scopeNotified(Builder $query): Builder
    {
        return $query->where('status', WaitlistStatusEnum::Notified);
    }

    public function scopeForSlot(Builder $query, string $businessId, string $date, string $time): Builder
    {
        return $query->where('business_id', $businessId)
            ->where('slot_date', $date)
            ->where('slot_time', $time);
    }

    public function scopeExpiredNotifications(Builder $query): Builder
    {
        return $query->where('status', WaitlistStatusEnum::Notified)
            ->where('expires_at', '<', now());
    }

    public function generateConfirmationToken(): string
    {
        $token = Str::random(64);
        $this->update(['confirmation_token' => $token]);

        return $token;
    }
}

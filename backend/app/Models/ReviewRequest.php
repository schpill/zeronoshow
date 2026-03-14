<?php

namespace App\Models;

use App\Enums\ReviewPlatformEnum;
use App\Enums\ReviewRequestStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewRequest extends Model
{
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'reservation_id',
        'business_id',
        'customer_id',
        'channel',
        'platform',
        'review_url',
        'short_code',
        'status',
        'sent_at',
        'clicked_at',
        'expires_at',
        'created_at',
    ];

    protected $casts = [
        'platform' => ReviewPlatformEnum::class,
        'status' => ReviewRequestStatusEnum::class,
        'sent_at' => 'datetime',
        'clicked_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ReviewRequestStatusEnum::Pending->value,
            ReviewRequestStatusEnum::Sent->value,
            ReviewRequestStatusEnum::Clicked->value,
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeoSession extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'channel_id',
        'sender_identifier',
        'active_business_id',
        'pending_selection',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'pending_selection' => 'boolean',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(LeoChannel::class, 'channel_id');
    }

    public function activeBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'active_business_id');
    }

    public function scopeForSender(Builder $query, string $senderIdentifier): Builder
    {
        return $query->where('sender_identifier', $senderIdentifier);
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }
}

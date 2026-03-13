<?php

namespace App\Models;

use App\Enums\LeoMessageDirection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeoMessageLog extends Model
{
    use HasFactory;
    use HasUuids;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'channel_id',
        'direction',
        'sender_identifier',
        'raw_message',
        'intent',
        'tool_called',
        'response_preview',
        'tokens_used',
        'latency_ms',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'direction' => LeoMessageDirection::class,
    ];

    protected static function booted(): void
    {
        static::updating(static fn (): bool => false);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(LeoChannel::class, 'channel_id');
    }
}

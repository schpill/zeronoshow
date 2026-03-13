<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeoMessageLog extends Model
{
    use HasFactory;
    use HasUuids;

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
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(LeoChannel::class, 'channel_id');
    }
}

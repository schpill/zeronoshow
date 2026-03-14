<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|WhatsAppConversationWindow active()
 * @method static \Illuminate\Database\Eloquent\Builder|WhatsAppConversationWindow forContact(string $channelId, string $phone, string $type)
 */
class WhatsAppConversationWindow extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'whatsapp_conversation_windows';

    public $timestamps = false;

    protected $fillable = [
        'channel_id',
        'contact_phone',
        'conversation_type',
        'opened_at',
        'expires_at',
        'cost_cents',
        'created_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(LeoChannel::class, 'channel_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('expires_at', '>', now());
    }

    public function scopeForContact(Builder $query, string $channelId, string $phone, string $type): void
    {
        $query->where('channel_id', $channelId)
            ->where('contact_phone', $phone)
            ->where('conversation_type', $type)
            ->where('expires_at', '>', now());
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeoChannel extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'business_id',
        'channel',
        'external_identifier',
        'bot_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(LeoSession::class, 'channel_id');
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(LeoMessageLog::class, 'channel_id');
    }

    public function maskedExternalIdentifier(): string
    {
        $value = $this->external_identifier;
        $length = mb_strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return '***'.mb_substr($value, -4);
    }
}

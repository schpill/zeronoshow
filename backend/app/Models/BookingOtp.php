<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BookingOtp extends Model
{
    use HasUuids;

    protected $fillable = [
        'phone',
        'code',
        'expires_at',
        'used_at',
        'ip_address',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function scopeValid(Builder $query, string $phone): Builder
    {
        return $query
            ->where('phone', $phone)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->where('attempts', '<', 5);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $q): void {
                $q->whereNotNull('used_at')
                    ->orWhere('expires_at', '<=', now());
            });
    }
}

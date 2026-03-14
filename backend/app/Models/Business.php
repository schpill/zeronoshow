<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property Carbon|null $trial_ends_at
 */
class Business extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'timezone',
        'subscription_status',
        'trial_ends_at',
        'stripe_customer_id',
        'stripe_subscription_id',
        'leo_addon_active',
        'leo_addon_stripe_item_id',
        'whatsapp_credit_cents',
        'whatsapp_monthly_cap_cents',
        'whatsapp_auto_renew',
        'whatsapp_last_renewed_at',
        'waitlist_enabled',
        'waitlist_notification_window_minutes',
        'waitlist_public_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'leo_addon_active' => 'boolean',
        'whatsapp_auto_renew' => 'boolean',
        'whatsapp_last_renewed_at' => 'datetime',
        'waitlist_enabled' => 'boolean',
        'waitlist_notification_window_minutes' => 'integer',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function leoChannel(): HasOne
    {
        return $this->hasOne(LeoChannel::class);
    }

    public function isOnActivePlan(): bool
    {
        return $this->subscription_status === 'active'
            || ($this->subscription_status === 'trial' && $this->trial_ends_at?->isFuture());
    }

    public function hasActiveLeoAddon(): bool
    {
        return $this->leo_addon_active;
    }
}

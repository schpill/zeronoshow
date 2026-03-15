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
        'voice_credit_cents',
        'voice_monthly_cap_cents',
        'voice_auto_renew',
        'voice_last_renewed_at',
        'voice_auto_call_enabled',
        'voice_auto_call_score_threshold',
        'voice_auto_call_min_party_size',
        'voice_retry_count',
        'voice_retry_delay_minutes',
        'waitlist_enabled',
        'waitlist_notification_window_minutes',
        'waitlist_public_token',
        'review_requests_enabled',
        'review_platform',
        'review_delay_hours',
        'google_place_id',
        'tripadvisor_location_id',
        'public_token',
        'onboarding_completed_at',
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
        'voice_auto_renew' => 'boolean',
        'voice_last_renewed_at' => 'datetime',
        'voice_auto_call_enabled' => 'boolean',
        'voice_auto_call_score_threshold' => 'integer',
        'voice_auto_call_min_party_size' => 'integer',
        'voice_retry_count' => 'integer',
        'voice_retry_delay_minutes' => 'integer',
        'waitlist_enabled' => 'boolean',
        'waitlist_notification_window_minutes' => 'integer',
        'review_requests_enabled' => 'boolean',
        'review_delay_hours' => 'integer',
        'onboarding_completed_at' => 'datetime',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function voiceCallLogs(): HasMany
    {
        return $this->hasMany(VoiceCallLog::class);
    }

    public function reviewRequests(): HasMany
    {
        return $this->hasMany(ReviewRequest::class);
    }

    public function leoChannel(): HasOne
    {
        return $this->hasOne(LeoChannel::class);
    }

    public function widgetSetting(): HasOne
    {
        return $this->hasOne(WidgetSetting::class);
    }

    public function resolveRouteBinding($value, $field = null): ?static
    {
        if ($field === 'public_token') {
            /** @var static|null */
            return static::query()->where('public_token', $value)->first();
        }

        /** @var static|null */
        return parent::resolveRouteBinding($value, $field);
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

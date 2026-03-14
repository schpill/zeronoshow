<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Str;

class WaitlistPublicLinkService
{
    public function generateToken(Business $business): string
    {
        $token = Str::random(32);
        $business->update(['waitlist_public_token' => $token]);
        return $token;
    }

    public function invalidateToken(Business $business): void
    {
        $business->update(['waitlist_public_token' => null]);
    }

    public function getPublicUrl(Business $business): ?string
    {
        if (! $business->waitlist_public_token) {
            return null;
        }

        return config('app.frontend_url') . "/join/{$business->waitlist_public_token}";
    }
}

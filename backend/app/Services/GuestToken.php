<?php

namespace App\Services;

use App\Exceptions\InvalidGuestTokenException;
use Illuminate\Support\Facades\Crypt;

class GuestToken
{
    public function issue(string $phone, string $businessId): string
    {
        $payload = [
            'phone' => $phone,
            'business_id' => $businessId,
            'issued_at' => now()->timestamp,
            'exp' => now()->addMinutes(30)->timestamp,
        ];

        return Crypt::encryptString(json_encode($payload));
    }

    public function verify(string $token): array
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new InvalidGuestTokenException;
        }

        if (
            ! is_array($payload)
            || ! isset($payload['phone'], $payload['business_id'], $payload['exp'])
            || now()->timestamp > $payload['exp']
        ) {
            throw new InvalidGuestTokenException;
        }

        return $payload;
    }
}

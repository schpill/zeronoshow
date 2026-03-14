<?php

namespace App\Services;

use App\Exceptions\OtpExpiredException;
use App\Exceptions\OtpInvalidException;
use App\Exceptions\OtpMaxAttemptsException;
use App\Exceptions\TooManyOtpRequestsException;
use App\Jobs\SendBookingOtpSms;
use App\Models\BookingOtp;

class BookingOtpService
{
    public function send(string $phone, ?string $ip = null): void
    {
        $recentCount = BookingOtp::query()
            ->where('phone', $phone)
            ->where('created_at', '>', now()->subMinutes(10))
            ->count();

        if ($recentCount >= 3) {
            throw new TooManyOtpRequestsException;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $ttlMinutes = (int) config('booking.otp_ttl_minutes', 10);

        BookingOtp::query()->create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes($ttlMinutes),
            'ip_address' => $ip,
        ]);

        SendBookingOtpSms::dispatch($phone, $code);
    }

    public function verify(string $phone, string $code): bool
    {
        $otp = BookingOtp::query()
            ->valid($phone)
            ->where('code', $code)
            ->first();

        if ($otp === null) {
            $attempted = BookingOtp::query()
                ->valid($phone)
                ->first();

            if ($attempted === null) {
                $expired = BookingOtp::query()
                    ->where('phone', $phone)
                    ->whereNull('used_at')
                    ->where('attempts', '>=', 5)
                    ->exists();

                if ($expired) {
                    throw new OtpMaxAttemptsException;
                }

                throw new OtpExpiredException;
            }

            $attempted->increment('attempts');

            if ($attempted->attempts >= 5) {
                throw new OtpMaxAttemptsException;
            }

            throw new OtpInvalidException;
        }

        $otp->update(['used_at' => now()]);

        return true;
    }
}

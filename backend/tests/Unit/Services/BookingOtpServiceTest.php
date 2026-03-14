<?php

namespace Tests\Unit\Services;

use App\Exceptions\OtpExpiredException;
use App\Exceptions\OtpInvalidException;
use App\Exceptions\OtpMaxAttemptsException;
use App\Exceptions\TooManyOtpRequestsException;
use App\Jobs\SendBookingOtpSms;
use App\Models\BookingOtp;
use App\Services\BookingOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BookingOtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingOtpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BookingOtpService::class);
    }

    public function test_send_generates_a_six_digit_code_and_stores_otp(): void
    {
        Queue::fake();

        $this->service->send('+33612345678');

        $otp = BookingOtp::query()->where('phone', '+33612345678')->first();
        $this->assertNotNull($otp);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp->code);
        $this->assertTrue($otp->expires_at->isFuture());
    }

    public function test_send_dispatches_send_booking_otp_sms_job(): void
    {
        Queue::fake();

        $this->service->send('+33612345678', '127.0.0.1');

        Queue::assertPushed(SendBookingOtpSms::class);
    }

    public function test_send_stores_ip_address_when_provided(): void
    {
        Queue::fake();

        $this->service->send('+33612345678', '1.2.3.4');

        $this->assertDatabaseHas('booking_otps', [
            'phone' => '+33612345678',
            'ip_address' => '1.2.3.4',
        ]);
    }

    public function test_send_throws_too_many_otp_requests_after_three_sends_in_ten_minutes(): void
    {
        Queue::fake();

        for ($i = 0; $i < 3; $i++) {
            $this->service->send('+33600000001');
        }

        $this->expectException(TooManyOtpRequestsException::class);
        $this->service->send('+33600000001');
    }

    public function test_send_does_not_throttle_different_phone_numbers(): void
    {
        Queue::fake();

        for ($i = 0; $i < 3; $i++) {
            $this->service->send('+33600000001');
        }

        // Different phone — should not be throttled
        $this->service->send('+33600000002');
        $this->assertDatabaseCount('booking_otps', 4);
    }

    public function test_verify_returns_true_for_valid_code(): void
    {
        BookingOtp::create([
            'phone' => '+33612345678',
            'code' => '654321',
            'expires_at' => now()->addMinutes(10),
        ]);

        $result = $this->service->verify('+33612345678', '654321');

        $this->assertTrue($result);
        $this->assertDatabaseHas('booking_otps', [
            'phone' => '+33612345678',
            'code' => '654321',
        ]);
        $otp = BookingOtp::query()->where('phone', '+33612345678')->first();
        $this->assertNotNull($otp->used_at);
    }

    public function test_verify_throws_otp_invalid_and_increments_attempts_on_wrong_code(): void
    {
        BookingOtp::create([
            'phone' => '+33612345678',
            'code' => '654321',
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $this->expectException(OtpInvalidException::class);
        $this->service->verify('+33612345678', '000000');

        $this->assertDatabaseHas('booking_otps', [
            'phone' => '+33612345678',
            'attempts' => 1,
        ]);
    }

    public function test_verify_throws_otp_expired_when_no_valid_otp_exists(): void
    {
        BookingOtp::create([
            'phone' => '+33612345678',
            'code' => '654321',
            'expires_at' => now()->subMinutes(1),
        ]);

        $this->expectException(OtpExpiredException::class);
        $this->service->verify('+33612345678', '654321');
    }

    public function test_verify_throws_otp_max_attempts_at_five_failed_attempts(): void
    {
        BookingOtp::create([
            'phone' => '+33612345678',
            'code' => '654321',
            'expires_at' => now()->addMinutes(10),
            'attempts' => 5,
        ]);

        $this->expectException(OtpMaxAttemptsException::class);
        $this->service->verify('+33612345678', '000000');
    }
}

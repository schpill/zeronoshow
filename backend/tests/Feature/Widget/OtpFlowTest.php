<?php

namespace Tests\Feature\Widget;

use App\Jobs\SendBookingOtpSms;
use App\Models\BookingOtp;
use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OtpFlowTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::factory()->create();
    }

    public function test_send_otp_sends_sms_and_stores_otp(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/public/widget/'.$this->business->public_token.'/otp/send', [
            'phone' => '+33612345678',
        ]);

        $response->assertOk();
        Queue::assertPushed(SendBookingOtpSms::class);
        $this->assertDatabaseCount('booking_otps', 1);
    }

    public function test_send_otp_is_throttled_after_3_sends_in_10_min(): void
    {
        Queue::fake();

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/public/widget/'.$this->business->public_token.'/otp/send', [
                'phone' => '+33612345678',
            ])->assertOk();
        }

        $this->postJson('/api/v1/public/widget/'.$this->business->public_token.'/otp/send', [
            'phone' => '+33612345678',
        ])->assertStatus(429);
    }

    public function test_verify_otp_returns_guest_token_on_valid_code(): void
    {
        BookingOtp::create([
            'phone' => '+33612345678',
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/public/widget/'.$this->business->public_token.'/otp/verify', [
            'phone' => '+33612345678',
            'code' => '123456',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['guest_token']);
    }

    public function test_verify_otp_fails_on_wrong_code_and_increments_attempts(): void
    {
        BookingOtp::create([
            'phone' => '+33612345678',
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $response = $this->postJson('/api/v1/public/widget/'.$this->business->public_token.'/otp/verify', [
            'phone' => '+33612345678',
            'code' => '999999',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('booking_otps', [
            'phone' => '+33612345678',
            'attempts' => 1,
        ]);
    }

    public function test_verify_otp_fails_after_5_wrong_attempts(): void
    {
        BookingOtp::create([
            'phone' => '+33612345678',
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
            'attempts' => 5,
        ]);

        $response = $this->postJson('/api/v1/public/widget/'.$this->business->public_token.'/otp/verify', [
            'phone' => '+33612345678',
            'code' => '999999',
        ]);

        $response->assertStatus(423);
    }

    public function test_verify_otp_fails_on_expired_otp(): void
    {
        BookingOtp::create([
            'phone' => '+33612345678',
            'code' => '123456',
            'expires_at' => now()->subMinutes(1),
        ]);

        $response = $this->postJson('/api/v1/public/widget/'.$this->business->public_token.'/otp/verify', [
            'phone' => '+33612345678',
            'code' => '123456',
        ]);

        $response->assertStatus(422);
    }
}

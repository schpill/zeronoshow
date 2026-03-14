<?php

namespace Tests\Feature\Widget;

use App\Models\BookingOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeOtpsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_expired_otps(): void
    {
        BookingOtp::create([
            'phone' => '+33600000001',
            'code' => '111111',
            'expires_at' => now()->subMinutes(1),
        ]);

        $this->artisan('booking:purge-otps')
            ->assertSuccessful()
            ->expectsOutputToContain('Deleted 1 expired OTP');

        $this->assertDatabaseCount('booking_otps', 0);
    }

    public function test_it_preserves_unexpired_otps(): void
    {
        BookingOtp::create([
            'phone' => '+33600000001',
            'code' => '111111',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->artisan('booking:purge-otps')->assertSuccessful();

        $this->assertDatabaseCount('booking_otps', 1);
    }

    public function test_dry_run_prints_count_without_deleting(): void
    {
        BookingOtp::create([
            'phone' => '+33600000001',
            'code' => '111111',
            'expires_at' => now()->subMinutes(1),
        ]);

        $this->artisan('booking:purge-otps --dry-run')
            ->assertSuccessful()
            ->expectsOutputToContain('Would delete 1 expired OTP');

        $this->assertDatabaseCount('booking_otps', 1);
    }

    public function test_it_outputs_zero_when_nothing_to_purge(): void
    {
        $this->artisan('booking:purge-otps')
            ->assertSuccessful()
            ->expectsOutputToContain('Deleted 0 expired OTP');
    }
}

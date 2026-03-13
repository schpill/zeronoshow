<?php

namespace Tests\Feature\Commands;

use App\Mail\TrialExpiryWarning;
use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TrialExpiryEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_email_to_business_with_trial_expiring_in_48_hours(): void
    {
        Mail::fake();
        Cache::flush();

        $business = Business::factory()->create([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addHours(48),
        ]);

        $this->artisan('trial:expiry-emails')->assertSuccessful();

        Mail::assertSent(TrialExpiryWarning::class, function (TrialExpiryWarning $mail) use ($business): bool {
            return $mail->hasTo($business->email);
        });
    }

    public function test_it_does_not_send_to_business_outside_the_target_window_or_already_active(): void
    {
        Mail::fake();
        Cache::flush();

        $tooEarly = Business::factory()->create([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addHours(72),
        ]);

        $active = Business::factory()->create([
            'subscription_status' => 'active',
            'trial_ends_at' => now()->addHours(48),
        ]);

        $this->artisan('trial:expiry-emails')->assertSuccessful();

        Mail::assertNotSent(TrialExpiryWarning::class, function (TrialExpiryWarning $mail) use ($tooEarly, $active): bool {
            return $mail->hasTo($tooEarly->email) || $mail->hasTo($active->email);
        });
    }

    public function test_it_does_not_send_duplicates_if_the_command_runs_twice_in_the_same_window(): void
    {
        Mail::fake();
        Cache::flush();

        $business = Business::factory()->create([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addHours(48),
        ]);

        $this->artisan('trial:expiry-emails')->assertSuccessful();
        $this->artisan('trial:expiry-emails')->assertSuccessful();

        Mail::assertSent(TrialExpiryWarning::class, 1);
        Mail::assertSent(TrialExpiryWarning::class, function (TrialExpiryWarning $mail) use ($business): bool {
            return $mail->hasTo($business->email);
        });
    }
}

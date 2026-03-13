<?php

namespace Tests\Feature\Commands;

use App\Mail\TrialExpiryWarning;
use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendTrialExpiryEmailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_warning_emails_for_trials_expiring_in_the_next_48_hours(): void
    {
        Mail::fake();

        $eligible = Business::factory()->create([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addHours(47),
        ]);
        Business::factory()->create([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(5),
        ]);

        $this->artisan('trial:send-expiry-warnings')
            ->assertExitCode(0);

        Mail::assertSent(TrialExpiryWarning::class, fn (TrialExpiryWarning $mail) => $mail->hasTo($eligible->email));
    }
}

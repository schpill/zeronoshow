<?php

namespace Tests\Unit\Jobs;

use App\Jobs\RenewVoiceCreditJob;
use App\Mail\VoiceCreditRenewedMail;
use App\Models\Business;
use App\Services\StripeService;
use App\Services\VoiceCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RenewVoiceCreditJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renews_voice_credits_successfully(): void
    {
        Mail::fake();

        $business = Business::factory()->create([
            'voice_auto_renew' => true,
            'voice_monthly_cap_cents' => 1000,
            'voice_last_renewed_at' => now()->subMonth(),
            'voice_credit_cents' => 0,
        ]);

        $stripe = $this->mock(StripeService::class);
        $stripe->shouldReceive('createVoiceInvoiceItem')->with($business, 1000)->once();
        $stripe->shouldReceive('finalizeAndPayInvoice')->with($business)->once();

        $credits = $this->mock(VoiceCreditService::class);
        $credits->shouldReceive('topUp')->with($business, 1000)->once();

        $job = new RenewVoiceCreditJob($business);
        $job->handle($stripe, $credits);

        $this->assertTrue($business->fresh()->voice_last_renewed_at->isToday());
        Mail::assertSent(VoiceCreditRenewedMail::class);
    }

    public function test_it_skips_when_auto_renew_is_disabled(): void
    {
        $business = Business::factory()->create([
            'voice_auto_renew' => false,
        ]);

        $stripe = $this->mock(StripeService::class);
        $stripe->shouldNotReceive('createVoiceInvoiceItem');

        $job = new RenewVoiceCreditJob($business);
        $job->handle($stripe, $this->mock(VoiceCreditService::class));
    }

    public function test_it_skips_if_already_renewed_this_month(): void
    {
        $business = Business::factory()->create([
            'voice_auto_renew' => true,
            'voice_monthly_cap_cents' => 1000,
            'voice_last_renewed_at' => now()->startOfMonth(),
        ]);

        $stripe = $this->mock(StripeService::class);
        $stripe->shouldNotReceive('createVoiceInvoiceItem');

        $job = new RenewVoiceCreditJob($business);
        $job->handle($stripe, $this->mock(VoiceCreditService::class));
    }
}

<?php

namespace Tests\Unit\Jobs;

use App\Jobs\RenewWhatsAppCreditJob;
use App\Mail\WhatsAppCreditRenewedMail;
use App\Models\Business;
use App\Leo\Tools\LeoWhatsAppCreditService;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RenewWhatsAppCreditJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renews_credits_successfully(): void
    {
        Mail::fake();

        $business = Business::factory()->create([
            'whatsapp_auto_renew' => true,
            'whatsapp_monthly_cap_cents' => 1000,
            'whatsapp_last_renewed_at' => now()->subMonth(),
            'whatsapp_credit_cents' => 0,
        ]);

        $stripeMock = $this->mock(StripeService::class);
        $stripeMock->shouldReceive('createWhatsAppInvoiceItem')->with($business, 1000)->once();
        $stripeMock->shouldReceive('finalizeAndPayInvoice')->with($business)->once();

        $waCreditsMock = $this->mock(LeoWhatsAppCreditService::class);
        $waCreditsMock->shouldReceive('topUp')->with($business, 1000)->once();

        $job = new RenewWhatsAppCreditJob($business);
        $job->handle($stripeMock, $waCreditsMock);

        $this->assertNotNull($business->fresh()->whatsapp_last_renewed_at);
        $this->assertTrue($business->fresh()->whatsapp_last_renewed_at->isToday());

        Mail::assertSent(WhatsAppCreditRenewedMail::class);
    }

    public function test_it_skips_if_auto_renew_disabled(): void
    {
        $business = Business::factory()->create(['whatsapp_auto_renew' => false]);

        $stripeMock = $this->mock(StripeService::class);
        $stripeMock->shouldNotReceive('createWhatsAppInvoiceItem');

        $job = new RenewWhatsAppCreditJob($business);
        $job->handle($stripeMock, $this->mock(LeoWhatsAppCreditService::class));
    }

    public function test_it_skips_if_already_renewed_this_month(): void
    {
        $business = Business::factory()->create([
            'whatsapp_auto_renew' => true,
            'whatsapp_monthly_cap_cents' => 1000,
            'whatsapp_last_renewed_at' => now()->startOfMonth(),
        ]);

        $stripeMock = $this->mock(StripeService::class);
        $stripeMock->shouldNotReceive('createWhatsAppInvoiceItem');

        $job = new RenewWhatsAppCreditJob($business);
        $job->handle($stripeMock, $this->mock(LeoWhatsAppCreditService::class));
    }
}

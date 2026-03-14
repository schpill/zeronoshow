<?php

namespace Tests\Unit\Leo;

use App\Events\LeoWhatsAppCreditExhaustedEvent;
use App\Events\LeoWhatsAppLowBalanceEvent;
use App\Leo\Tools\LeoWhatsAppCreditService;
use App\Models\Business;
use App\Models\LeoChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeoWhatsAppCreditServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeoWhatsAppCreditService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->service = new LeoWhatsAppCreditService;
    }

    public function test_get_balance(): void
    {
        $business = Business::factory()->create(['whatsapp_credit_cents' => 500]);
        $this->assertEquals(500, $this->service->getBalance($business));
    }

    public function test_has_sufficient_credit(): void
    {
        $business = Business::factory()->create(['whatsapp_credit_cents' => 10]);

        $this->assertTrue($this->service->hasSufficientCredit($business, 5));
        $this->assertTrue($this->service->hasSufficientCredit($business, 10));
        $this->assertFalse($this->service->hasSufficientCredit($business, 11));
    }

    public function test_deduct_updates_balance(): void
    {
        $business = Business::factory()->create(['whatsapp_credit_cents' => 100]);

        $this->service->deduct($business, 30);

        $this->assertEquals(70, $business->fresh()->whatsapp_credit_cents);
    }

    public function test_deduct_triggers_low_balance_event(): void
    {
        Event::fake();
        config(['leo.whatsapp.low_balance_threshold_cents' => 100]);

        $business = Business::factory()->create(['whatsapp_credit_cents' => 150]);

        $this->service->deduct($business, 60); // 90 cents left

        Event::assertDispatched(LeoWhatsAppLowBalanceEvent::class, function ($event) use ($business) {
            return $event->business->id === $business->id && $event->balanceCents === 90;
        });
    }

    public function test_deduct_exhausts_credit_and_suspends_channel(): void
    {
        Event::fake();

        $business = Business::factory()->create(['whatsapp_credit_cents' => 50]);
        $channel = LeoChannel::factory()->create([
            'business_id' => $business->id,
            'channel' => 'whatsapp',
            'is_active' => true,
        ]);

        $this->service->deduct($business, 50);

        $this->assertEquals(0, $business->fresh()->whatsapp_credit_cents);
        $this->assertFalse($channel->fresh()->is_active);

        Event::assertDispatched(LeoWhatsAppCreditExhaustedEvent::class, function ($event) use ($business, $channel) {
            return $event->business->id === $business->id && $event->channel->id === $channel->id;
        });
    }

    public function test_top_up_adds_credit_and_reactivates_channel(): void
    {
        $business = Business::factory()->create(['whatsapp_credit_cents' => 0]);
        $channel = LeoChannel::factory()->create([
            'business_id' => $business->id,
            'channel' => 'whatsapp',
            'is_active' => false,
        ]);

        $this->service->topUp($business, 1000);

        $this->assertEquals(1000, $business->fresh()->whatsapp_credit_cents);
        $this->assertTrue($channel->fresh()->is_active);
    }

    public function test_get_conversation_cost(): void
    {
        config([
            'leo.whatsapp.cost_service_cents' => 5,
            'leo.whatsapp.cost_utility_cents' => 10,
        ]);

        $this->assertEquals(5, $this->service->getConversationCost('service'));
        $this->assertEquals(10, $this->service->getConversationCost('utility'));
        $this->assertEquals(0, $this->service->getConversationCost('unknown'));
    }
}

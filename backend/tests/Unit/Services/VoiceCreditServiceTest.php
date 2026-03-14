<?php

namespace Tests\Unit\Services;

use App\Events\VoiceCreditExhaustedEvent;
use App\Events\VoiceLowBalanceEvent;
use App\Models\Business;
use App\Services\VoiceCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class VoiceCreditServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_balance_returns_voice_credit_cents(): void
    {
        $business = Business::factory()->create([
            'voice_credit_cents' => 240,
        ]);

        $service = app(VoiceCreditService::class);

        $this->assertSame(240, $service->getBalance($business));
    }

    public function test_has_sufficient_credit_checks_available_balance(): void
    {
        $business = Business::factory()->create([
            'voice_credit_cents' => 80,
        ]);

        $service = app(VoiceCreditService::class);

        $this->assertTrue($service->hasSufficientCredit($business, 80));
        $this->assertFalse($service->hasSufficientCredit($business, 81));
    }

    public function test_deduct_decrements_balance_and_suspends_auto_call_at_zero(): void
    {
        Event::fake();

        $business = Business::factory()->create([
            'voice_credit_cents' => 8,
            'voice_auto_call_enabled' => true,
        ]);

        $service = app(VoiceCreditService::class);
        $service->deduct($business, 8);

        $fresh = $business->fresh();

        $this->assertSame(0, $fresh->voice_credit_cents);
        $this->assertFalse($fresh->voice_auto_call_enabled);

        Event::assertDispatched(VoiceCreditExhaustedEvent::class);
    }

    public function test_deduct_dispatches_low_balance_event_when_threshold_crossed(): void
    {
        Event::fake();
        config(['leo.voice.low_balance_threshold_cents' => 100]);

        $business = Business::factory()->create([
            'voice_credit_cents' => 150,
            'voice_auto_call_enabled' => true,
        ]);

        $service = app(VoiceCreditService::class);
        $service->deduct($business, 80);

        $this->assertSame(70, $business->fresh()->voice_credit_cents);
        Event::assertDispatched(VoiceLowBalanceEvent::class);
    }

    public function test_top_up_reenables_auto_call_and_adds_balance(): void
    {
        $business = Business::factory()->create([
            'voice_credit_cents' => 0,
            'voice_auto_call_enabled' => false,
        ]);

        $service = app(VoiceCreditService::class);
        $service->topUp($business, 200);

        $fresh = $business->fresh();

        $this->assertSame(200, $fresh->voice_credit_cents);
        $this->assertTrue($fresh->voice_auto_call_enabled);
    }

    public function test_get_call_cost_returns_configured_value(): void
    {
        config(['services.twilio.voice_cost_per_call_cents' => 12]);

        $service = app(VoiceCreditService::class);

        $this->assertSame(12, $service->getCallCost());
    }
}

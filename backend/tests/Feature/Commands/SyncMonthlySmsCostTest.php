<?php

namespace Tests\Feature\Commands;

use App\Models\Business;
use App\Models\Reservation;
use App\Models\SmsLog;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncMonthlySmsCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_invoice_item_for_the_previous_month_sms_cost(): void
    {
        $business = Business::factory()->create([
            'subscription_status' => 'active',
            'stripe_customer_id' => 'cus_test_123',
        ]);
        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
        ]);
        SmsLog::factory()->create([
            'business_id' => $business->id,
            'reservation_id' => $reservation->id,
            'cost_eur' => 1.234,
            'created_at' => now()->subMonth()->startOfMonth()->addDays(3),
        ]);

        $spy = new class
        {
            /** @var array<int, array<string, mixed>> */
            public array $calls = [];
        };

        $this->instance(StripeService::class, new class($spy)
        {
            public function __construct(private object $spy) {}

            public function createInvoiceItem(Business $business, int $amountInCents, string $period): void
            {
                $this->spy->calls[] = [
                    'business_id' => $business->id,
                    'amount_in_cents' => $amountInCents,
                    'period' => $period,
                ];
            }
        });

        $this->artisan('billing:sync-sms-cost --month='.now()->subMonth()->format('Y-m'))
            ->assertExitCode(0);

        $this->assertSame([[
            'business_id' => $business->id,
            'amount_in_cents' => 123,
            'period' => now()->subMonth()->format('Y-m'),
        ]], $spy->calls);
    }
}

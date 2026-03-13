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
            'status' => 'delivered',
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

    public function test_it_skips_cancelled_businesses_and_zero_cost_months(): void
    {
        $cancelled = Business::factory()->create([
            'subscription_status' => 'cancelled',
            'stripe_customer_id' => 'cus_cancelled',
        ]);
        $active = Business::factory()->create([
            'subscription_status' => 'active',
            'stripe_customer_id' => 'cus_active',
        ]);

        $cancelledReservation = Reservation::factory()->create(['business_id' => $cancelled->id]);
        $activeReservation = Reservation::factory()->create(['business_id' => $active->id]);

        SmsLog::factory()->create([
            'business_id' => $cancelled->id,
            'reservation_id' => $cancelledReservation->id,
            'status' => 'delivered',
            'cost_eur' => 0.50,
            'created_at' => now()->subMonth()->startOfMonth()->addDays(3),
        ]);
        SmsLog::factory()->create([
            'business_id' => $active->id,
            'reservation_id' => $activeReservation->id,
            'status' => 'failed',
            'cost_eur' => 0.50,
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
                $this->spy->calls[] = compact('business', 'amountInCents', 'period');
            }
        });

        $this->artisan('billing:sync-sms-cost --month='.now()->subMonth()->format('Y-m'))
            ->assertExitCode(0);

        $this->assertSame([], $spy->calls);
    }
}

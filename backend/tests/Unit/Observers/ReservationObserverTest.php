<?php

namespace Tests\Unit\Observers;

use App\Jobs\RecalculateReliabilityScore;
use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReservationObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_score_recalculation_and_increments_no_shows(): void
    {
        Queue::fake();
        $customer = Customer::factory()->create();
        $reservation = Reservation::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending_reminder',
        ]);

        $reservation->update([
            'status' => 'no_show',
            'status_changed_at' => now(),
        ]);

        Queue::assertPushed(RecalculateReliabilityScore::class, function (RecalculateReliabilityScore $job) use ($customer): bool {
            return $job->customerId === $customer->id;
        });

        $customer->refresh();

        $this->assertSame(1, $customer->no_shows_count);
        $this->assertSame(0, $customer->shows_count);
    }

    public function test_it_dispatches_score_recalculation_and_increments_shows_for_show_status(): void
    {
        Queue::fake();
        $customer = Customer::factory()->create();
        $reservation = Reservation::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending_reminder',
        ]);

        $reservation->update([
            'status' => 'show',
            'status_changed_at' => now(),
        ]);

        Queue::assertPushed(RecalculateReliabilityScore::class);

        $customer->refresh();

        $this->assertSame(1, $customer->shows_count);
        $this->assertSame(0, $customer->no_shows_count);
    }

    public function test_it_dispatches_score_recalculation_for_confirmed_status(): void
    {
        Queue::fake();
        $customer = Customer::factory()->create();
        $reservation = Reservation::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending_reminder',
        ]);

        $reservation->update([
            'status' => 'confirmed',
            'status_changed_at' => now(),
        ]);

        Queue::assertPushed(RecalculateReliabilityScore::class);

        $customer->refresh();

        $this->assertSame(1, $customer->shows_count);
    }

    public function test_it_does_not_dispatch_for_non_terminal_status_changes(): void
    {
        Queue::fake();
        $customer = Customer::factory()->create();
        $reservation = Reservation::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending_verification',
        ]);

        $reservation->update([
            'status' => 'pending_reminder',
            'status_changed_at' => now(),
        ]);

        Queue::assertNothingPushed();

        $customer->refresh();

        $this->assertSame(0, $customer->shows_count);
        $this->assertSame(0, $customer->no_shows_count);
    }

    public function test_it_reverses_the_previous_terminal_counters_when_status_is_corrected(): void
    {
        Queue::fake();
        $customer = Customer::factory()->create([
            'shows_count' => 0,
            'no_shows_count' => 1,
        ]);
        $reservation = Reservation::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'no_show',
        ]);

        $reservation->update([
            'status' => 'show',
            'status_changed_at' => now(),
        ]);

        Queue::assertPushed(RecalculateReliabilityScore::class);

        $customer->refresh();

        $this->assertSame(1, $customer->shows_count);
        $this->assertSame(0, $customer->no_shows_count);
    }
}

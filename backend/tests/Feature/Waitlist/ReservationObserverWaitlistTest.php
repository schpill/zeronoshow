<?php

namespace Tests\Feature\Waitlist;

use App\Jobs\NotifyWaitlistJob;
use App\Models\Business;
use App\Models\Reservation;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReservationObserverWaitlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_reservation_triggers_waitlist_notification(): void
    {
        Queue::fake();

        $business = Business::factory()->create(['waitlist_enabled' => true]);
        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'confirmed',
        ]);

        WaitlistEntry::factory()->create([
            'business_id' => $business->id,
            'slot_date' => $reservation->scheduled_at->format('Y-m-d'),
            'slot_time' => $reservation->scheduled_at->format('H:i:00'),
            'status' => 'pending',
        ]);

        $reservation->update(['status' => 'cancelled_by_client']);

        Queue::assertPushed(NotifyWaitlistJob::class);
    }

    public function test_no_show_triggers_waitlist_notification(): void
    {
        Queue::fake();

        $business = Business::factory()->create(['waitlist_enabled' => true]);
        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'confirmed',
        ]);

        WaitlistEntry::factory()->create([
            'business_id' => $business->id,
            'slot_date' => $reservation->scheduled_at->format('Y-m-d'),
            'slot_time' => $reservation->scheduled_at->format('H:i:00'),
            'status' => 'pending',
        ]);

        $reservation->update(['status' => 'no_show']);

        Queue::assertPushed(NotifyWaitlistJob::class);
    }
}

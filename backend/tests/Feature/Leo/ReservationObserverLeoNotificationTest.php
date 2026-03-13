<?php

namespace Tests\Feature\Leo;

use App\Jobs\SendLeoNotificationJob;
use App\Models\Business;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReservationObserverLeoNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_a_leo_notification_job_when_reservation_is_cancelled_by_client(): void
    {
        Queue::fake();

        $business = Business::factory()->create([
            'leo_addon_active' => true,
        ]);

        $this->seedLeoChannel($business);

        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'confirmed',
        ]);

        $reservation->update([
            'status' => 'cancelled_by_client',
            'status_changed_at' => now(),
        ]);

        Queue::assertPushed(SendLeoNotificationJob::class, function (SendLeoNotificationJob $job) use ($reservation): bool {
            return $job->reservationId === $reservation->id && $job->event === 'cancelled_by_client';
        });
    }

    public function test_it_dispatches_a_leo_notification_job_when_reservation_is_marked_no_show(): void
    {
        Queue::fake();

        $business = Business::factory()->create([
            'leo_addon_active' => true,
        ]);

        $this->seedLeoChannel($business);

        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'confirmed',
        ]);

        $reservation->update([
            'status' => 'no_show',
            'status_changed_at' => now(),
        ]);

        Queue::assertPushed(SendLeoNotificationJob::class, function (SendLeoNotificationJob $job) use ($reservation): bool {
            return $job->reservationId === $reservation->id && $job->event === 'no_show';
        });
    }

    public function test_it_does_not_dispatch_when_business_has_no_active_leo_channel(): void
    {
        Queue::fake();

        $business = Business::factory()->create([
            'leo_addon_active' => false,
        ]);

        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'status' => 'confirmed',
        ]);

        $reservation->update([
            'status' => 'cancelled_by_client',
            'status_changed_at' => now(),
        ]);

        Queue::assertNotPushed(SendLeoNotificationJob::class);
    }

    private function seedLeoChannel(Business $business): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => $business->email,
            'password' => 'password123',
        ]);

        DB::table('leo_channels')->insert([
            'id' => (string) Str::uuid(),
            'business_id' => $business->id,
            'channel' => 'telegram',
            'external_identifier' => '123456789',
            'bot_name' => 'Léo',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

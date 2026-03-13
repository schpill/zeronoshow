<?php

namespace Tests\Unit\Leo;

use App\Jobs\SendLeoNotificationJob;
use App\Models\Business;
use App\Models\LeoMessageLog;
use App\Models\Reservation;
use App\Services\Leo\LeoThrottleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendLeoNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_a_cancellation_notification_without_exposing_a_phone_number(): void
    {
        $reservation = $this->reservationWithChannel();
        $job = new SendLeoNotificationJob($reservation->id, 'cancelled_by_client');

        $job->handle(new class extends LeoThrottleService
        {
            public function isThrottled(string $key): bool
            {
                return false;
            }
        });

        $log = LeoMessageLog::query()->latest('created_at')->firstOrFail();

        $this->assertSame('cancelled_by_client', $log->intent);
        $this->assertStringContainsString('Annulation client', $log->raw_message);
        $this->assertStringNotContainsString('0601020304', $log->raw_message);
    }

    public function test_it_logs_a_no_show_notification(): void
    {
        $reservation = $this->reservationWithChannel();
        $job = new SendLeoNotificationJob($reservation->id, 'no_show');

        $job->handle(new class extends LeoThrottleService
        {
            public function isThrottled(string $key): bool
            {
                return false;
            }
        });

        $log = LeoMessageLog::query()->latest('created_at')->firstOrFail();

        $this->assertSame('no_show', $log->intent);
        $this->assertStringContainsString('No-show', $log->raw_message);
    }

    public function test_it_logs_a_throttled_event_and_skips_the_regular_notification(): void
    {
        $reservation = $this->reservationWithChannel();
        $job = new SendLeoNotificationJob($reservation->id, 'cancelled_by_client');

        $job->handle(new class extends LeoThrottleService
        {
            public function isThrottled(string $key): bool
            {
                return true;
            }
        });

        $this->assertDatabaseHas('leo_message_logs', [
            'channel_id' => $reservation->business->leoChannel?->id,
            'intent' => 'throttled',
            'raw_message' => 'Notification Léo bloquée par le throttle.',
        ]);
    }

    public function test_it_targets_the_default_queue(): void
    {
        $job = new SendLeoNotificationJob('reservation-id', 'no_show');

        $this->assertSame('default', $job->queue);
    }

    private function reservationWithChannel(): Reservation
    {
        $business = Business::factory()->create([
            'timezone' => 'Europe/Paris',
            'leo_addon_active' => true,
        ]);
        $business->leoChannel()->create([
            'channel' => 'telegram',
            'external_identifier' => '123456789',
            'bot_name' => 'Léo',
            'is_active' => true,
        ]);

        return Reservation::factory()->create([
            'business_id' => $business->id,
            'customer_name' => 'Alice Martin',
            'scheduled_at' => now()->addHour(),
            'notes' => 'Client 0601020304',
            'status' => 'confirmed',
        ])->load('business');
    }
}

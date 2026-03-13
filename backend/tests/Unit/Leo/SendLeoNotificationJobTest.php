<?php

namespace Tests\Unit\Leo;

use App\Jobs\SendLeoNotificationJob;
use App\Models\Business;
use App\Models\LeoMessageLog;
use App\Models\Reservation;
use App\Services\Leo\LeoThrottleService;
use App\Services\Leo\TelegramChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class SendLeoNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_a_cancellation_notification_without_exposing_a_phone_number(): void
    {
        config()->set('services.telegram.token', 'telegram-token');
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $reservation = $this->reservationWithChannel();
        $job = new SendLeoNotificationJob($reservation->id, 'cancelled_by_client');

        $job->handle(
            new class extends LeoThrottleService
            {
                public function isThrottled(string $key): bool
                {
                    return false;
                }
            },
            app(TelegramChannel::class),
        );

        $log = LeoMessageLog::query()->latest('created_at')->firstOrFail();

        $this->assertSame('cancelled_by_client', $log->intent);
        $this->assertStringContainsString('Annulation client', $log->raw_message);
        $this->assertStringNotContainsString('0601020304', $log->raw_message);

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/sendMessage')
                && str_contains((string) $request['text'], 'Annulation client')
                && ! str_contains((string) $request['text'], '0601020304');
        });
    }

    public function test_it_logs_a_no_show_notification(): void
    {
        config()->set('services.telegram.token', 'telegram-token');
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        config()->set('services.telegram.token', 'telegram-token');
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $reservation = $this->reservationWithChannel();
        $job = new SendLeoNotificationJob($reservation->id, 'no_show');

        $job->handle(
            new class extends LeoThrottleService
            {
                public function isThrottled(string $key): bool
                {
                    return false;
                }
            },
            app(TelegramChannel::class),
        );

        $log = LeoMessageLog::query()->latest('created_at')->firstOrFail();

        $this->assertSame('no_show', $log->intent);
        $this->assertStringContainsString('No-show', $log->raw_message);

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/sendMessage')
                && str_contains((string) $request['text'], 'No-show');
        });
    }

    public function test_it_logs_a_throttled_event_and_skips_the_regular_notification(): void
    {
        config()->set('services.telegram.token', 'telegram-token');
        Http::fake();

        $reservation = $this->reservationWithChannel();
        $job = new SendLeoNotificationJob($reservation->id, 'cancelled_by_client');

        $job->handle(
            new class extends LeoThrottleService
            {
                public function isThrottled(string $key): bool
                {
                    return true;
                }
            },
            app(TelegramChannel::class),
        );

        $this->assertDatabaseHas('leo_message_logs', [
            'channel_id' => $reservation->business->leoChannel?->id,
            'intent' => 'throttled',
            'raw_message' => 'Notification Léo bloquée par le throttle.',
        ]);

        Http::assertNothingSent();
    }

    public function test_it_targets_the_default_queue(): void
    {
        $job = new SendLeoNotificationJob('reservation-id', 'no_show');

        $this->assertSame('default', $job->queue);
    }

    private function reservationWithChannel(): Reservation
    {
        $businessId = (string) Str::uuid();
        DB::table('businesses')->insert([
            'id' => $businessId,
            'name' => 'Le Salon',
            'email' => 'owner@example.com',
            'password' => bcrypt('password123'),
            'phone' => '+33612345678',
            'timezone' => 'Europe/Paris',
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $business = Business::query()->findOrFail($businessId);
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

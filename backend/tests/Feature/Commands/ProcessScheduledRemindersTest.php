<?php

namespace Tests\Feature\Commands;

use App\Jobs\SendReminderSms;
use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessScheduledRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_two_hour_reminder_for_average_tier_clients(): void
    {
        Queue::fake();
        $customer = Customer::factory()->create([
            'score_tier' => 'average',
        ]);
        $reservation = Reservation::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending_reminder',
            'scheduled_at' => now()->addHours(2),
        ]);

        $this->artisan('reminders:process')->assertExitCode(0);

        Queue::assertPushed(SendReminderSms::class, function (SendReminderSms $job) use ($reservation): bool {
            return $job->reservationId === $reservation->id && $job->reminderType === '2h';
        });
    }

    public function test_it_dispatches_both_reminders_for_at_risk_clients(): void
    {
        Queue::fake();
        $customer = Customer::factory()->create([
            'score_tier' => 'at_risk',
        ]);
        $twoHour = Reservation::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending_reminder',
            'scheduled_at' => now()->addHours(2),
        ]);
        $thirtyMinute = Reservation::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending_reminder',
            'scheduled_at' => now()->addMinutes(30),
        ]);

        $this->artisan('reminders:process')->assertExitCode(0);

        Queue::assertPushed(SendReminderSms::class, function (SendReminderSms $job) use ($twoHour): bool {
            return $job->reservationId === $twoHour->id && $job->reminderType === '2h';
        });
        Queue::assertPushed(SendReminderSms::class, function (SendReminderSms $job) use ($thirtyMinute): bool {
            return $job->reservationId === $thirtyMinute->id && $job->reminderType === '30m';
        });
    }

    public function test_it_does_not_dispatch_two_hour_reminders_for_reliable_clients(): void
    {
        Queue::fake();
        $customer = Customer::factory()->create([
            'score_tier' => 'reliable',
        ]);
        Reservation::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending_reminder',
            'scheduled_at' => now()->addHours(2),
        ]);

        $this->artisan('reminders:process')->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_it_does_not_dispatch_when_reminders_are_already_sent(): void
    {
        Queue::fake();
        $customer = Customer::factory()->create([
            'score_tier' => 'at_risk',
        ]);
        Reservation::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending_reminder',
            'scheduled_at' => now()->addHours(2),
            'reminder_2h_sent' => true,
        ]);
        Reservation::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending_reminder',
            'scheduled_at' => now()->addMinutes(30),
            'reminder_30m_sent' => true,
        ]);

        $this->artisan('reminders:process')->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_it_dispatches_nothing_when_no_reservations_match(): void
    {
        Queue::fake();

        $this->artisan('reminders:process')->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_it_does_not_dispatch_the_same_reminder_twice_when_run_twice_before_jobs_execute(): void
    {
        Queue::fake();
        $customer = Customer::factory()->create([
            'score_tier' => 'average',
        ]);
        $reservation = Reservation::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending_reminder',
            'scheduled_at' => now()->addHours(2),
        ]);

        $this->artisan('reminders:process')->assertExitCode(0);
        $this->artisan('reminders:process')->assertExitCode(0);

        Queue::assertPushed(SendReminderSms::class, 1);
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'reminder_2h_sent' => true,
        ]);
    }
}

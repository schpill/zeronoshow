<?php

use App\Jobs\SendReminderSms;
use App\Models\Reservation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reminders:process', function () {
    $twoHourCount = DB::transaction(function (): int {
        $reservations = Reservation::query()
            ->with('customer')
            ->whereIn('status', ['pending_reminder', 'confirmed'])
            ->where('reminder_2h_sent', false)
            ->whereBetween('scheduled_at', [now()->addHour()->addMinutes(55), now()->addHours(2)->addMinutes(5)])
            ->lockForUpdate()
            ->get()
            ->filter(fn (Reservation $reservation): bool => $reservation->customer->getScoreTier() !== 'reliable');

        foreach ($reservations as $reservation) {
            $reservation->forceFill([
                'reminder_2h_sent' => true,
            ])->save();

            SendReminderSms::dispatch($reservation->id, '2h', true);
        }

        return $reservations->count();
    });

    $thirtyMinuteCount = DB::transaction(function (): int {
        $reservations = Reservation::query()
            ->with('customer')
            ->whereIn('status', ['pending_reminder', 'confirmed'])
            ->where('reminder_30m_sent', false)
            ->whereBetween('scheduled_at', [now()->addMinutes(25), now()->addMinutes(35)])
            ->lockForUpdate()
            ->get()
            ->filter(fn (Reservation $reservation): bool => $reservation->customer->getScoreTier() === 'at_risk');

        foreach ($reservations as $reservation) {
            $reservation->forceFill([
                'reminder_30m_sent' => true,
            ])->save();

            SendReminderSms::dispatch($reservation->id, '30m', true);
        }

        return $reservations->count();
    });

    $this->info("Processed {$twoHourCount} two-hour reminders and {$thirtyMinuteCount} thirty-minute reminders.");
})->purpose('Dispatch scheduled reminder SMS jobs');

Artisan::command('reservations:auto-cancel', function () {
    $expiredTokens = Reservation::query()
        ->where('status', 'pending_verification')
        ->whereNotNull('token_expires_at')
        ->where('token_expires_at', '<', now())
        ->update([
            'status' => 'cancelled_no_confirmation',
            'status_changed_at' => now(),
            'confirmation_token' => null,
            'token_expires_at' => null,
        ]);

    $expiredPendingReminders = Reservation::query()
        ->whereIn('status', ['pending_reminder', 'confirmed'])
        ->where('reminder_30m_sent', true)
        ->whereNotNull('confirmation_token')
        ->where('scheduled_at', '<', now()->subMinutes(15))
        ->update([
            'status' => 'cancelled_no_confirmation',
            'status_changed_at' => now(),
            'confirmation_token' => null,
            'token_expires_at' => null,
        ]);

    $this->info("Cancelled {$expiredTokens} token expired reservations and {$expiredPendingReminders} pending reminder reservations.");
})->purpose('Auto-cancel expired unconfirmed reservations');

Artisan::command('sms-logs:purge', function () {
    $this->info('sms-logs:purge stub - full implementation planned for Phase 4.');
})->purpose('Stub SMS log purge command for scheduler wiring');

Schedule::command('reminders:process')->everyMinute()->withoutOverlapping(10);
Schedule::command('reservations:auto-cancel')->everyMinute()->withoutOverlapping(10);
Schedule::command('sms-logs:purge')->dailyAt('03:00');

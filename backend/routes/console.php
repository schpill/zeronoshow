<?php

use App\Console\Commands\PurgeLeoMessageLogs;
use App\Console\Commands\PurgeSmsLogs;
use App\Console\Commands\RunSmokeTests;
use App\Enums\WaitlistStatusEnum;
use App\Jobs\ExpireWaitlistNotificationsJob;
use App\Jobs\SendReminderSms;
use App\Mail\TrialExpiryWarning;
use App\Models\Business;
use App\Models\Reservation;
use App\Models\SmsLog;
use App\Models\WaitlistEntry;
use App\Services\StripeService;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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

Artisan::command('waitlist:expire-notifications', function () {
    ExpireWaitlistNotificationsJob::dispatch();
    $this->info('Dispatched ExpireWaitlistNotificationsJob.');
})->purpose('Expire waitlist notifications and cascade to next entries');

Artisan::command('waitlist:purge-expired', function () {
    $count = WaitlistEntry::query()
        ->whereIn('status', [WaitlistStatusEnum::Expired, WaitlistStatusEnum::Declined])
        ->where('created_at', '<', now()->subDays(30))
        ->delete();
    $this->info("Purged {$count} old waitlist entries.");
})->purpose('Purge old expired or declined waitlist entries');

$sendTrialExpiryWarnings = function () {
    $windowStart = now()->addHours(47)->subMinute();
    $windowEnd = now()->addHours(49)->addMinute();
    $sentCount = 0;

    Business::query()
        ->where('subscription_status', 'trial')
        ->whereBetween('trial_ends_at', [$windowStart, $windowEnd])
        ->orderBy('trial_ends_at')
        ->get()
        ->each(function (Business $business) use (&$sentCount): void {
            $key = sprintf('trial-expiry-email:%s:%s', $business->id, now()->format('YmdH'));

            if (! Cache::add($key, true, now()->addHours(2))) {
                return;
            }

            Mail::to($business->email)->queue(new TrialExpiryWarning($business));
            $sentCount++;
        });

    $this->info("Queued {$sentCount} trial expiry emails.");
};

$syncMonthlySmsCost = function () {
    $service = app(StripeService::class);
    $monthOption = $this->option('month');
    $start = $monthOption
        ? Carbon::createFromFormat('Y-m', (string) $monthOption)->startOfMonth()
        : now()->subMonthNoOverflow()->startOfMonth();
    $end = $start->copy()->endOfMonth();
    $count = 0;

    Business::query()
        ->where('subscription_status', 'active')
        ->orderBy('name')
        ->get()
        ->each(function (Business $business) use ($service, $start, $end, &$count): void {
            $amount = (float) SmsLog::query()
                ->where('business_id', $business->id)
                ->where('status', 'delivered')
                ->whereBetween('created_at', [$start->utc(), $end->utc()])
                ->sum('cost_eur');

            if ($amount <= 0) {
                return;
            }

            $service->createInvoiceItem(
                $business,
                (int) floor($amount * 100),
                $start->format('Y-m'),
            );

            $count++;
        });

    $this->info("Created {$count} SMS invoice items.");
};

Artisan::command('trial:expiry-emails', $sendTrialExpiryWarnings)
    ->purpose('Send trial expiry warning emails');

Artisan::command('billing:sync-sms-cost {--month=}', $syncMonthlySmsCost)
    ->purpose('Create monthly Stripe invoice items for SMS costs');

Schedule::command('reminders:process')->everyMinute()->withoutOverlapping(10);
Schedule::command('reservations:auto-cancel')->everyMinute()->withoutOverlapping(10);
Schedule::command('waitlist:expire-notifications')->everyMinute()->withoutOverlapping(10);
Schedule::command('waitlist:purge-expired')->daily()->withoutOverlapping(10);
Schedule::command('trial:expiry-emails')->hourly()->withoutOverlapping(10);
Schedule::command('billing:sync-sms-cost')->monthlyOn(1, '06:00')->withoutOverlapping(60);
Schedule::command(PurgeSmsLogs::class)->dailyAt('03:30');
Schedule::command(PurgeLeoMessageLogs::class)->monthly()->withoutOverlapping(60);
Schedule::command('whatsapp:purge-windows')->daily()->withoutOverlapping(10);
Schedule::command('leo:renew-whatsapp-credits')->monthlyOn(1, '06:00')->withoutOverlapping(60);
Schedule::command(RunSmokeTests::class)->dailyAt('04:00')->environments(['production']);

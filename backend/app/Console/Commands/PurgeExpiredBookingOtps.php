<?php

namespace App\Console\Commands;

use App\Models\BookingOtp;
use Illuminate\Console\Command;

class PurgeExpiredBookingOtps extends Command
{
    protected $signature = 'booking:purge-otps {--dry-run : Print count without deleting}';

    protected $description = 'Purge expired booking OTPs from the database';

    public function handle(): int
    {
        $expired = BookingOtp::query()->expired();
        $count = $expired->count();

        if ($this->option('dry-run')) {
            $this->info("Would delete {$count} expired OTP(s).");

            return self::SUCCESS;
        }

        $expired->delete();

        $this->info("Deleted {$count} expired OTP(s).");

        return self::SUCCESS;
    }
}

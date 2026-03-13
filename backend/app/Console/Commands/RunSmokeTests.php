<?php

namespace App\Console\Commands;

use App\Jobs\SendVerificationSms;
use App\Models\Business;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;

class RunSmokeTests extends Command
{
    protected $signature = 'smoke:test';

    protected $description = 'Run critical path smoke checks for production deploys';

    public function handle(): int
    {
        Queue::fake();

        $business = Business::factory()->create([
            'email' => 'smoke-test@zeronoshow.local',
        ]);

        try {
            $reservation = Reservation::factory()->create([
                'business_id' => $business->id,
                'scheduled_at' => now()->addDay(),
                'status' => 'pending_verification',
            ]);

            SendVerificationSms::dispatch($reservation->id);
            Queue::assertPushed(SendVerificationSms::class);

            $reservation->update([
                'status' => 'confirmed',
                'status_changed_at' => now(),
            ]);

            $healthResponse = app()->handle(Request::create('/api/v1/health', 'GET'));

            if ($healthResponse->getStatusCode() !== 200) {
                $this->error('Health check failed during smoke test');

                return self::FAILURE;
            }

            $this->info('Smoke tests passed');

            return self::SUCCESS;
        } finally {
            Reservation::query()->where('business_id', $business->id)->delete();
            $business->delete();
        }
    }
}

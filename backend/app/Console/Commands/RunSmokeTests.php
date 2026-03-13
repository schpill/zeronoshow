<?php

namespace App\Console\Commands;

use App\Http\Controllers\ConfirmationController;
use App\Jobs\SendVerificationSms;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\SmsLog;
use App\Services\Contracts\SmsServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class RunSmokeTests extends Command
{
    protected $signature = 'smoke:test';

    protected $description = 'Run critical path smoke checks for production deploys';

    public function handle(): int
    {
        $business = Business::factory()->create([
            'email' => 'smoke-test@zeronoshow.local',
        ]);
        $customer = Customer::factory()->create([
            'phone' => '+33600000001',
        ]);
        $reservation = null;

        try {
            app()->instance(SmsServiceInterface::class, new class implements SmsServiceInterface
            {
                public function send(SmsLog $smsLog): SmsLog
                {
                    $smsLog->update([
                        'twilio_sid' => 'SMOKE-TEST-SID',
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);

                    return $smsLog->fresh();
                }

                public function validateWebhookSignature(Request $request): bool
                {
                    return true;
                }
            });

            $reservation = Reservation::query()->create([
                'business_id' => $business->id,
                'customer_id' => $customer->id,
                'customer_name' => 'Smoke Test Customer',
                'scheduled_at' => now()->addDay(),
                'status' => 'pending_verification',
                'phone_verified' => false,
                'confirmation_token' => (string) fake()->uuid(),
                'token_expires_at' => now()->addHours(8),
            ]);

            SendVerificationSms::dispatchSync($reservation->id);

            if (! SmsLog::query()->where('reservation_id', $reservation->id)->exists()) {
                $this->error('Verification SMS was not generated during smoke test');

                return self::FAILURE;
            }

            $originalQueueConnection = config('queue.default');

            config(['queue.default' => 'sync']);

            try {
                $confirmResponse = app(ConfirmationController::class)->confirm(
                    Request::create(
                        "/c/{$reservation->confirmation_token}/confirm",
                        'POST',
                        ['action' => 'confirm'],
                    ),
                    $reservation->confirmation_token,
                );
            } finally {
                config(['queue.default' => $originalQueueConnection]);
            }

            if ($confirmResponse->getStatusCode() !== 200) {
                $this->error('Confirmation flow failed during smoke test');

                return self::FAILURE;
            }

            $healthResponse = app()->handle(Request::create('/api/v1/health', 'GET'));

            if ($healthResponse->getStatusCode() !== 200) {
                $this->error('Health check failed during smoke test');

                return self::FAILURE;
            }

            $this->info('Smoke tests passed');

            return self::SUCCESS;
        } finally {
            if ($reservation) {
                SmsLog::query()->where('reservation_id', $reservation->id)->delete();
            }
            app()->forgetInstance(SmsServiceInterface::class);
            Reservation::query()->where('business_id', $business->id)->delete();
            $customer->delete();
            $business->delete();
        }
    }
}

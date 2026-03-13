<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendVerificationSms;
use App\Models\Reservation;
use App\Models\SmsLog;
use App\Services\Contracts\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class SendVerificationSmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_sms_log_and_uses_the_sms_service(): void
    {
        Route::name('confirmation.show')->get('/fake-show/{token}', fn () => 'ok');
        Route::name('confirmation.cancel')->get('/fake-cancel/{token}', fn () => 'ok');

        $reservation = Reservation::factory()->create();

        $service = Mockery::mock(SmsServiceInterface::class);
        $service->shouldReceive('send')->once()->andReturnUsing(function (SmsLog $smsLog): SmsLog {
            $smsLog->update([
                'twilio_sid' => 'SM123',
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return $smsLog->fresh();
        });
        $service->shouldReceive('validateWebhookSignature')->andReturnTrue();

        app()->instance(SmsServiceInterface::class, $service);

        (new SendVerificationSms($reservation->id))->handle($service);

        $this->assertDatabaseHas('sms_logs', [
            'reservation_id' => $reservation->id,
            'twilio_sid' => 'SM123',
            'status' => 'sent',
        ]);
        $smsLog = SmsLog::query()->where('reservation_id', $reservation->id)->latest('created_at')->firstOrFail();

        $this->assertStringContainsString("/c/{$reservation->confirmation_token}", $smsLog->body);
        $this->assertStringContainsString("/c/{$reservation->confirmation_token}/cancel", $smsLog->body);
        $this->assertStringNotContainsString('/confirm', $smsLog->body);
    }

    public function test_it_does_not_send_when_reservation_can_no_longer_be_confirmed(): void
    {
        $reservation = Reservation::factory()->create([
            'confirmation_token' => null,
        ]);

        $service = Mockery::mock(SmsServiceInterface::class);
        $service->shouldNotReceive('send');

        (new SendVerificationSms($reservation->id))->handle($service);

        $this->assertDatabaseMissing('sms_logs', [
            'reservation_id' => $reservation->id,
        ]);
    }
}

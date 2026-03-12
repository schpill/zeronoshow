<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendVerificationSms;
use App\Models\Reservation;
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
        Route::name('confirmation.confirm')->post('/fake-confirm/{token}', fn () => 'ok');

        $reservation = Reservation::factory()->create();

        $service = Mockery::mock(SmsServiceInterface::class);
        $service->shouldReceive('send')->once()->andReturn([
            'sid' => 'SM123',
            'status' => 'sent',
        ]);
        $service->shouldReceive('validateWebhookSignature')->andReturnTrue();

        app()->instance(SmsServiceInterface::class, $service);

        (new SendVerificationSms($reservation->id))->handle($service);

        $this->assertDatabaseHas('sms_logs', [
            'reservation_id' => $reservation->id,
            'twilio_sid' => 'SM123',
            'status' => 'sent',
        ]);
    }
}

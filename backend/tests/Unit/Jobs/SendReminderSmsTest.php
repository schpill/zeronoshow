<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendReminderSms;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\SmsLog;
use App\Services\Contracts\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class SendReminderSmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::name('confirmation.show')->get('/fake-show/{token}', fn () => 'ok');
        Route::name('confirmation.cancel')->get('/fake-cancel/{token}', fn () => 'ok');
    }

    public function test_it_sends_the_average_tier_two_hour_reminder(): void
    {
        $customer = Customer::factory()->create([
            'reliability_score' => 80,
            'score_tier' => 'average',
        ]);
        $reservation = Reservation::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending_reminder',
            'reminder_2h_sent' => false,
        ]);

        $service = Mockery::mock(SmsServiceInterface::class);
        $service->shouldReceive('send')->once()->andReturnUsing(fn (SmsLog $smsLog): SmsLog => $smsLog);

        (new SendReminderSms($reservation->id, '2h'))->handle($service);

        $log = SmsLog::query()->where('reservation_id', $reservation->id)->latest('created_at')->firstOrFail();

        $this->assertSame('reminder_2h', $log->type);
        $this->assertStringContainsString('dans 2h', $log->body);
        $this->assertStringContainsString('Merci de confirmer', $log->body);
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'reminder_2h_sent' => true,
        ]);
    }

    public function test_it_sends_the_at_risk_two_hour_reminder(): void
    {
        $customer = Customer::factory()->create([
            'reliability_score' => 40,
            'score_tier' => 'at_risk',
        ]);
        $reservation = Reservation::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending_reminder',
        ]);

        $service = Mockery::mock(SmsServiceInterface::class);
        $service->shouldReceive('send')->once()->andReturnUsing(fn (SmsLog $smsLog): SmsLog => $smsLog);

        (new SendReminderSms($reservation->id, '2h'))->handle($service);

        $log = SmsLog::query()->where('reservation_id', $reservation->id)->latest('created_at')->firstOrFail();

        $this->assertStringContainsString('Réponse requise', $log->body);
    }

    public function test_it_sends_the_thirty_minute_reminder(): void
    {
        $customer = Customer::factory()->create([
            'reliability_score' => 50,
            'score_tier' => 'at_risk',
        ]);
        $reservation = Reservation::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending_reminder',
            'reminder_30m_sent' => false,
        ]);

        $service = Mockery::mock(SmsServiceInterface::class);
        $service->shouldReceive('send')->once()->andReturnUsing(fn (SmsLog $smsLog): SmsLog => $smsLog);

        (new SendReminderSms($reservation->id, '30m'))->handle($service);

        $log = SmsLog::query()->where('reservation_id', $reservation->id)->latest('created_at')->firstOrFail();

        $this->assertSame('reminder_30m', $log->type);
        $this->assertStringContainsString('dans 30 min', $log->body);
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'reminder_30m_sent' => true,
        ]);
    }

    public function test_it_aborts_when_customer_has_opted_out(): void
    {
        $customer = Customer::factory()->create([
            'opted_out' => true,
            'opted_out_at' => now(),
            'score_tier' => 'average',
        ]);
        $reservation = Reservation::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending_reminder',
        ]);

        $service = Mockery::mock(SmsServiceInterface::class);
        $service->shouldNotReceive('send');

        (new SendReminderSms($reservation->id, '2h'))->handle($service);

        $this->assertDatabaseCount('sms_logs', 0);
    }

    public function test_it_aborts_when_reservation_is_cancelled(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'cancelled_no_confirmation',
        ]);

        $service = Mockery::mock(SmsServiceInterface::class);
        $service->shouldNotReceive('send');

        (new SendReminderSms($reservation->id, '2h'))->handle($service);

        $this->assertDatabaseCount('sms_logs', 0);
    }

    public function test_it_aborts_when_the_requested_reminder_has_already_been_sent(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => 'pending_reminder',
            'reminder_2h_sent' => true,
        ]);

        $service = Mockery::mock(SmsServiceInterface::class);
        $service->shouldNotReceive('send');

        (new SendReminderSms($reservation->id, '2h'))->handle($service);

        $this->assertDatabaseCount('sms_logs', 0);
    }
}

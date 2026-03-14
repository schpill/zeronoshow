<?php

namespace Tests\Feature\Voice;

use App\Jobs\NotifyWaitlistJob;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\VoiceCallLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class VoiceGatherControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_digit_1_confirms_reservation_and_returns_confirmation_twiml(): void
    {
        $log = $this->createVoiceLog();

        $response = $this->post('/api/v1/webhooks/voice/gather/'.$log->id, [
            'Digits' => '1',
        ]);

        $response->assertOk();
        $response->assertSee('Votre réservation est confirmée', false);
        $this->assertSame('confirmed', $log->fresh()->status->value);
        $this->assertSame('confirmed', $log->reservation->fresh()->status);
    }

    public function test_digit_2_cancels_reservation_and_dispatches_waitlist_when_enabled(): void
    {
        Queue::fake();

        $business = Business::factory()->create([
            'waitlist_enabled' => true,
        ]);
        $customer = Customer::factory()->create();
        $reservation = Reservation::factory()->for($business)->for($customer)->create([
            'status' => 'pending_verification',
        ]);
        $log = VoiceCallLog::query()->create([
            'reservation_id' => $reservation->id,
            'business_id' => $business->id,
            'to_phone' => $customer->phone,
            'attempt_number' => 1,
            'status' => 'initiated',
        ]);

        $response = $this->post('/api/v1/webhooks/voice/gather/'.$log->id, [
            'Digits' => '2',
        ]);

        $response->assertOk();
        $response->assertSee('Votre réservation a été annulée', false);
        $this->assertSame('declined', $log->fresh()->status->value);
        $this->assertSame('cancelled_by_client', $reservation->fresh()->status);
        Queue::assertPushed(NotifyWaitlistJob::class);
    }

    public function test_invalid_digit_reasks_for_input(): void
    {
        $log = $this->createVoiceLog();

        $response = $this->post('/api/v1/webhooks/voice/gather/'.$log->id, [
            'Digits' => '9',
        ]);

        $response->assertOk();
        $response->assertSee('Touche non reconnue', false);
        $this->assertSame('initiated', $log->fresh()->status->value);
    }

    private function createVoiceLog(): VoiceCallLog
    {
        $reservation = Reservation::factory()->create([
            'status' => 'pending_verification',
        ]);

        return VoiceCallLog::query()->create([
            'reservation_id' => $reservation->id,
            'business_id' => $reservation->business_id,
            'to_phone' => $reservation->customer->phone,
            'attempt_number' => 1,
            'status' => 'initiated',
        ]);
    }
}

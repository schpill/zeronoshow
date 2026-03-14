<?php

namespace Tests\Unit\Services;

use App\Exceptions\VoiceInsufficientCreditException;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use App\Services\VoiceCallService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VoiceCallServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.twilio.sid' => 'AC123',
            'services.twilio.token' => 'token-123',
            'services.twilio.voice_number' => '+33123456789',
            'services.twilio.voice_cost_per_call_cents' => 8,
            'app.url' => 'https://api.example.test',
        ]);
    }

    public function test_initiate_call_creates_log_and_stores_twilio_sid(): void
    {
        Http::fake([
            'https://api.twilio.com/*' => Http::response([
                'sid' => 'CA1234567890',
            ], 201),
        ]);

        $reservation = $this->makeReservationWithBusiness([
            'voice_credit_cents' => 50,
        ]);

        $service = app(VoiceCallService::class);
        $log = $service->initiateCall($reservation);

        $this->assertDatabaseHas('voice_call_logs', [
            'id' => $log->id,
            'reservation_id' => $reservation->id,
            'business_id' => $reservation->business_id,
            'to_phone' => $reservation->customer->phone,
            'attempt_number' => 1,
            'status' => 'initiated',
            'twilio_call_sid' => 'CA1234567890',
            'cost_cents' => 8,
        ]);
        $this->assertSame(42, $reservation->business->fresh()->voice_credit_cents);
    }

    public function test_initiate_call_posts_expected_twilio_payload(): void
    {
        Http::fake([
            'https://api.twilio.com/*' => Http::response(['sid' => 'CA999'], 201),
        ]);

        $reservation = $this->makeReservationWithBusiness([
            'voice_credit_cents' => 20,
        ]);

        app(VoiceCallService::class)->initiateCall($reservation, 2);

        Http::assertSent(function ($request) use ($reservation): bool {
            $data = $request->data();

            return $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/AC123/Calls.json'
                && $data['To'] === $reservation->customer->phone
                && $data['From'] === '+33123456789'
                && str_starts_with($data['Url'], 'https://api.example.test/api/v1/webhooks/voice/twiml/')
                && str_starts_with($data['StatusCallback'], 'https://api.example.test/api/v1/webhooks/voice/status/')
                && $data['StatusCallbackMethod'] === 'POST';
        });
    }

    public function test_initiate_call_throws_when_balance_is_insufficient(): void
    {
        Http::fake();

        $reservation = $this->makeReservationWithBusiness([
            'voice_credit_cents' => 4,
        ]);

        $this->expectException(VoiceInsufficientCreditException::class);

        app(VoiceCallService::class)->initiateCall($reservation);
    }

    private function makeReservationWithBusiness(array $businessAttributes = []): Reservation
    {
        $business = Business::factory()->create(array_merge([
            'voice_credit_cents' => 50,
            'voice_auto_call_enabled' => true,
        ], $businessAttributes));
        $customer = Customer::factory()->create([
            'phone' => '+33611223344',
        ]);

        return Reservation::factory()->for($business)->for($customer)->create([
            'customer_name' => 'Alice Martin',
            'scheduled_at' => now()->addDay()->setTime(20, 0),
            'guests' => 4,
            'status' => 'pending_verification',
        ])->fresh(['business', 'customer']);
    }
}

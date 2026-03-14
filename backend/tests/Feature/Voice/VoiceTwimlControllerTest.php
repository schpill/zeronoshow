<?php

namespace Tests\Feature\Voice;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\VoiceCallLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceTwimlControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_valid_xml_for_a_log(): void
    {
        $log = $this->createVoiceLog();

        $response = $this->get("/api/v1/webhooks/voice/twiml/{$log->id}");

        $response->assertOk();
        $response->assertHeader('content-type', 'text/xml; charset=UTF-8');
        $response->assertSee('Polly.Léa', false);
        $response->assertSee('Appuyez sur 1 pour confirmer', false);
        $response->assertSee("/api/v1/webhooks/voice/gather/{$log->id}", false);
    }

    public function test_it_returns_not_found_for_unknown_log(): void
    {
        $response = $this->get('/api/v1/webhooks/voice/twiml/00000000-0000-0000-0000-000000000000');

        $response->assertNotFound();
    }

    private function createVoiceLog(): VoiceCallLog
    {
        $reservation = Reservation::factory()
            ->for(Business::factory()->create(['name' => 'Le Bistrot']))
            ->for(Customer::factory()->create(['phone' => '+33611223344']))
            ->create([
                'customer_name' => 'Alice Martin',
                'scheduled_at' => now()->addDay()->setTime(20, 30),
                'guests' => 3,
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

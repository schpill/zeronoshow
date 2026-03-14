<?php

namespace Tests\Feature\Widget;

use App\Models\Business;
use App\Models\WidgetSetting;
use App\Services\GuestToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicReservationStoreTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private GuestToken $guestToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::factory()->create();
        WidgetSetting::create([
            'business_id' => $this->business->id,
            'max_party_size' => 20,
            'advance_booking_days' => 60,
            'is_enabled' => true,
        ]);
        $this->guestToken = new GuestToken;
    }

    private function makeGuestToken(string $phone = '+33612345678'): string
    {
        return $this->guestToken->issue($phone, $this->business->id);
    }

    public function test_it_creates_reservation_with_source_widget(): void
    {
        $date = now()->addDay()->format('Y-m-d');
        $token = $this->makeGuestToken('+33612345678');

        $response = $this->postJson('/api/v1/public/widget/'.$this->business->public_token.'/reservations', [
            'guest_token' => $token,
            'party_size' => 2,
            'date' => $date,
            'time' => '12:00',
            'guest_name' => 'Jean Dupont',
            'guest_phone' => '+33612345678',
        ]);

        $response->assertCreated()
            ->assertJsonPath('reservation.status', 'pending_verification');

        $this->assertDatabaseHas('reservations', [
            'business_id' => $this->business->id,
            'source' => 'widget',
            'customer_name' => 'Jean Dupont',
            'guests' => 2,
        ]);
    }

    public function test_it_returns_422_on_party_size_exceeds_max(): void
    {
        $date = now()->addDay()->format('Y-m-d');
        $token = $this->makeGuestToken('+33612345678');

        $response = $this->postJson('/api/v1/public/widget/'.$this->business->public_token.'/reservations', [
            'guest_token' => $token,
            'party_size' => 25,
            'date' => $date,
            'time' => '12:00',
            'guest_name' => 'Jean Dupont',
            'guest_phone' => '+33612345678',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['party_size']);
    }

    public function test_it_returns_422_on_invalid_guest_token(): void
    {
        $date = now()->addDay()->format('Y-m-d');

        $response = $this->postJson('/api/v1/public/widget/'.$this->business->public_token.'/reservations', [
            'guest_token' => 'invalid-token',
            'party_size' => 2,
            'date' => $date,
            'time' => '12:00',
            'guest_name' => 'Jean Dupont',
            'guest_phone' => '+33612345678',
        ]);

        $response->assertStatus(422);
    }
}

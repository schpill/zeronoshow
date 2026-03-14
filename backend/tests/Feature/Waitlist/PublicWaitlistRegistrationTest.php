<?php

namespace Tests\Feature\Waitlist;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicWaitlistRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_public_waitlist_page(): void
    {
        $business = Business::factory()->create([
            'waitlist_enabled' => true,
            'waitlist_public_token' => 'public-token',
        ]);

        $response = $this->getJson('/join/public-token');

        $response->assertOk()
            ->assertJsonPath('business_name', $business->name);
    }

    public function test_can_register_via_public_link(): void
    {
        $business = Business::factory()->create([
            'waitlist_enabled' => true,
            'waitlist_public_token' => 'public-token',
        ]);

        $payload = [
            'slot_date' => now()->addDay()->format('Y-m-d'),
            'slot_time' => '19:30',
            'client_name' => 'Jane Guest',
            'client_phone' => '+33687654321',
            'party_size' => 4,
        ];

        $response = $this->postJson('/join/public-token', $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('waitlist_entries', [
            'business_id' => $business->id,
            'client_name' => 'Jane Guest',
        ]);
    }
}

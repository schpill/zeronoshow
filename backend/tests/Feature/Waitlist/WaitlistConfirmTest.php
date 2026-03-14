<?php

namespace Tests\Feature\Waitlist;

use App\Enums\WaitlistStatusEnum;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaitlistConfirmTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_confirm_slot_via_public_url(): void
    {
        $entry = WaitlistEntry::factory()->create([
            'status' => WaitlistStatusEnum::Notified,
            'expires_at' => now()->addMinutes(15),
            'confirmation_token' => 'valid-token',
        ]);

        $response = $this->get('/waitlist/confirm/valid-token');

        $response->assertRedirect();
        $this->assertEquals(WaitlistStatusEnum::Confirmed, $entry->fresh()->status);
        $this->assertDatabaseHas('reservations', [
            'business_id' => $entry->business_id,
            'customer_name' => $entry->client_name,
        ]);
    }

    public function test_can_decline_slot_via_public_url(): void
    {
        $entry = WaitlistEntry::factory()->create([
            'status' => WaitlistStatusEnum::Notified,
            'confirmation_token' => 'valid-token',
        ]);

        $response = $this->get('/waitlist/decline/valid-token');

        $response->assertRedirect();
        $this->assertEquals(WaitlistStatusEnum::Declined, $entry->fresh()->status);
    }

    public function test_redirects_to_expired_if_token_invalid(): void
    {
        $response = $this->get('/waitlist/confirm/invalid-token');

        $response->assertRedirect(config('app.frontend_url').'/waitlist/expired');
    }
}

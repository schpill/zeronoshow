<?php

namespace Tests\Feature\Waitlist;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WaitlistSettingsTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::factory()->create();
        Sanctum::actingAs($this->business);
    }

    public function test_can_retrieve_waitlist_settings(): void
    {
        $response = $this->getJson('/api/v1/waitlist/settings');

        $response->assertOk()
            ->assertJsonStructure([
                'waitlist_enabled',
                'waitlist_notification_window_minutes',
                'waitlist_public_token',
                'public_registration_url',
            ]);
    }

    public function test_can_update_waitlist_settings(): void
    {
        $response = $this->patchJson('/api/v1/waitlist/settings', [
            'waitlist_enabled' => true,
            'waitlist_notification_window_minutes' => 20,
        ]);

        $response->assertOk();
        $this->assertTrue($this->business->fresh()->waitlist_enabled);
        $this->assertEquals(20, $this->business->fresh()->waitlist_notification_window_minutes);
    }

    public function test_can_regenerate_public_link(): void
    {
        $oldToken = $this->business->waitlist_public_token;

        $response = $this->postJson('/api/v1/waitlist/settings/regenerate-link');

        $response->assertOk();
        $this->assertNotEquals($oldToken, $this->business->fresh()->waitlist_public_token);
    }
}

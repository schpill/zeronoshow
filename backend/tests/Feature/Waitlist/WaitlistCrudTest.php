<?php

namespace Tests\Feature\Waitlist;

use App\Models\Business;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WaitlistCrudTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::factory()->create();
        Sanctum::actingAs($this->business);
    }

    public function test_can_list_waitlist_entries(): void
    {
        WaitlistEntry::factory()->count(3)->create(['business_id' => $this->business->id]);

        $response = $this->getJson('/api/v1/waitlist');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_waitlist_entry(): void
    {
        $payload = [
            'slot_date' => now()->addDay()->format('Y-m-d'),
            'slot_time' => '19:30',
            'client_name' => 'John Doe',
            'client_phone' => '+33612345678',
            'party_size' => 2,
        ];

        $response = $this->postJson('/api/v1/waitlist', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.client_name', 'John Doe');

        $this->assertDatabaseHas('waitlist_entries', [
            'business_id' => $this->business->id,
            'client_name' => 'John Doe',
        ]);
    }

    public function test_can_delete_pending_waitlist_entry(): void
    {
        $entry = WaitlistEntry::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'pending',
        ]);

        $response = $this->deleteJson("/api/v1/waitlist/{$entry->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('waitlist_entries', ['id' => $entry->id]);
    }

    public function test_cannot_delete_notified_waitlist_entry(): void
    {
        $entry = WaitlistEntry::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'notified',
        ]);

        $response = $this->deleteJson("/api/v1/waitlist/{$entry->id}");

        $response->assertUnprocessable();
    }
}

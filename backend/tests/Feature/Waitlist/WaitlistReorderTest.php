<?php

namespace Tests\Feature\Waitlist;

use App\Models\Business;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WaitlistReorderTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::factory()->create();
        Sanctum::actingAs($this->business);
    }

    public function test_can_reorder_waitlist_entries(): void
    {
        $entry1 = WaitlistEntry::factory()->create([
            'business_id' => $this->business->id,
            'priority_order' => 1,
        ]);
        $entry2 = WaitlistEntry::factory()->create([
            'business_id' => $this->business->id,
            'priority_order' => 2,
        ]);

        $response = $this->postJson('/api/v1/waitlist/reorder', [
            'ordered_ids' => [$entry2->id, $entry1->id],
        ]);

        $response->assertOk();
        $this->assertEquals(1, $entry2->fresh()->priority_order);
        $this->assertEquals(2, $entry1->fresh()->priority_order);
    }
}

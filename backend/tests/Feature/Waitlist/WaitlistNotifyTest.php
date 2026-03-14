<?php

namespace Tests\Feature\Waitlist;

use App\Jobs\NotifyWaitlistJob;
use App\Models\Business;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WaitlistNotifyTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::factory()->create();
        Sanctum::actingAs($this->business);
    }

    public function test_can_manually_trigger_notification(): void
    {
        Queue::fake();

        $entry = WaitlistEntry::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/waitlist/{$entry->id}/notify");

        $response->assertAccepted();
        Queue::assertPushed(NotifyWaitlistJob::class);
    }
}

<?php

namespace Tests\Unit\Leo;

use App\Models\Business;
use App\Models\LeoChannel;
use App\Models\LeoSession;
use App\Services\Leo\LeoSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LeoSessionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_and_resolve_return_the_active_business(): void
    {
        $business = Business::factory()->create();
        $channel = LeoChannel::factory()->create(['business_id' => $business->id]);
        $service = new LeoSessionService;

        $service->set($channel->id, 'sender-1', $business->id, 300);

        $resolved = $service->resolve($channel->id, 'sender-1');

        $this->assertNotNull($resolved);
        $this->assertSame($business->id, $resolved->id);
    }

    public function test_resolve_ignores_expired_sessions(): void
    {
        $business = Business::factory()->create();
        $channel = LeoChannel::factory()->create(['business_id' => $business->id]);

        LeoSession::query()->create([
            'channel_id' => $channel->id,
            'sender_identifier' => 'sender-1',
            'active_business_id' => $business->id,
            'pending_selection' => false,
            'expires_at' => now()->subMinute(),
        ]);

        $service = new LeoSessionService;

        $this->assertNull($service->resolve($channel->id, 'sender-1'));
    }

    public function test_clear_removes_a_sender_session(): void
    {
        $business = Business::factory()->create();
        $channel = LeoChannel::factory()->create(['business_id' => $business->id]);
        $service = new LeoSessionService;

        $service->set($channel->id, 'sender-1', $business->id, 300);
        $service->clear($channel->id, 'sender-1');

        $this->assertDatabaseMissing('leo_sessions', [
            'channel_id' => $channel->id,
            'sender_identifier' => 'sender-1',
        ]);
    }

    public function test_find_pending_selection_returns_a_matching_pending_session(): void
    {
        $business = Business::factory()->create();
        $channel = LeoChannel::factory()->create(['business_id' => $business->id]);

        $pending = LeoSession::query()->create([
            'channel_id' => $channel->id,
            'sender_identifier' => 'sender-1',
            'active_business_id' => null,
            'pending_selection' => true,
            'expires_at' => now()->addMinutes(5),
        ]);

        $service = new LeoSessionService;

        $resolved = $service->findPendingSelection(new Collection([$channel]), 'sender-1');

        $this->assertNotNull($resolved);
        $this->assertSame($pending->id, $resolved->id);
    }

    public function test_clear_pending_selections_removes_only_pending_sessions_for_the_sender(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $channel = LeoChannel::factory()->create(['business_id' => $business->id]);
        $otherChannel = LeoChannel::factory()->create(['business_id' => $otherBusiness->id]);
        $service = new LeoSessionService;

        LeoSession::query()->create([
            'channel_id' => $channel->id,
            'sender_identifier' => 'sender-1',
            'active_business_id' => null,
            'pending_selection' => true,
            'expires_at' => now()->addMinutes(5),
        ]);
        LeoSession::query()->create([
            'channel_id' => $otherChannel->id,
            'sender_identifier' => 'sender-2',
            'active_business_id' => null,
            'pending_selection' => true,
            'expires_at' => now()->addMinutes(5),
        ]);

        $service->clearPendingSelections(new Collection([$channel, $otherChannel]), 'sender-1');

        $this->assertDatabaseMissing('leo_sessions', [
            'channel_id' => $channel->id,
            'sender_identifier' => 'sender-1',
        ]);
        $this->assertDatabaseHas('leo_sessions', [
            'channel_id' => $otherChannel->id,
            'sender_identifier' => 'sender-2',
        ]);
    }

    public function test_set_does_not_rewrite_an_existing_valid_session_with_the_same_payload(): void
    {
        Carbon::setTestNow('2026-03-13 10:00:00');

        $business = Business::factory()->create();
        $channel = LeoChannel::factory()->create(['business_id' => $business->id]);
        $service = new LeoSessionService;

        $service->set($channel->id, 'sender-1', $business->id, 300);

        $session = LeoSession::query()
            ->where('channel_id', $channel->id)
            ->where('sender_identifier', 'sender-1')
            ->firstOrFail();

        $originalUpdatedAt = $session->updated_at;
        $originalExpiry = $session->expires_at;

        Carbon::setTestNow('2026-03-13 10:01:00');

        $service->set($channel->id, 'sender-1', $business->id, 300);

        $session->refresh();

        $this->assertTrue($session->updated_at->equalTo($originalUpdatedAt));
        $this->assertTrue($session->expires_at->equalTo($originalExpiry));

        Carbon::setTestNow();
    }

    public function test_set_purges_expired_sessions_before_creating_a_new_one(): void
    {
        $business = Business::factory()->create();
        $channel = LeoChannel::factory()->create(['business_id' => $business->id]);

        LeoSession::query()->create([
            'channel_id' => $channel->id,
            'sender_identifier' => 'expired-sender',
            'active_business_id' => $business->id,
            'pending_selection' => false,
            'expires_at' => now()->subMinute(),
        ]);

        $service = new LeoSessionService;

        $service->set($channel->id, 'sender-1', $business->id, 300);

        $this->assertDatabaseMissing('leo_sessions', [
            'sender_identifier' => 'expired-sender',
        ]);
        $this->assertDatabaseHas('leo_sessions', [
            'channel_id' => $channel->id,
            'sender_identifier' => 'sender-1',
        ]);
    }
}

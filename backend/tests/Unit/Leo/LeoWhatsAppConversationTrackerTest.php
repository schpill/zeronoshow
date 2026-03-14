<?php

namespace Tests\Unit\Leo;

use App\Models\LeoChannel;
use App\Models\WhatsAppConversationWindow;
use App\Leo\Tools\LeoWhatsAppConversationTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeoWhatsAppConversationTrackerTest extends TestCase
{
    use RefreshDatabase;

    private LeoWhatsAppConversationTracker $tracker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tracker = new LeoWhatsAppConversationTracker;
    }

    public function test_has_active_window(): void
    {
        $channel = LeoChannel::factory()->create();

        $this->assertFalse($this->tracker->hasActiveWindow($channel->id, '12345', 'service'));

        WhatsAppConversationWindow::create([
            'channel_id' => $channel->id,
            'contact_phone' => '12345',
            'conversation_type' => 'service',
            'opened_at' => now(),
            'expires_at' => now()->addHours(24),
            'cost_cents' => 5,
        ]);

        $this->assertTrue($this->tracker->hasActiveWindow($channel->id, '12345', 'service'));
        $this->assertFalse($this->tracker->hasActiveWindow($channel->id, '12345', 'utility'));
        $this->assertFalse($this->tracker->hasActiveWindow($channel->id, '54321', 'service'));
    }

    public function test_expired_window_is_not_active(): void
    {
        $channel = LeoChannel::factory()->create();

        WhatsAppConversationWindow::create([
            'channel_id' => $channel->id,
            'contact_phone' => '12345',
            'conversation_type' => 'service',
            'opened_at' => now()->subHours(25),
            'expires_at' => now()->subHour(),
            'cost_cents' => 5,
        ]);

        $this->assertFalse($this->tracker->hasActiveWindow($channel->id, '12345', 'service'));
    }

    public function test_open_window_creates_record(): void
    {
        $channel = LeoChannel::factory()->create();

        $window = $this->tracker->openWindow($channel->id, '12345', 'service', 5);

        $this->assertDatabaseHas('whatsapp_conversation_windows', [
            'id' => $window->id,
            'channel_id' => $channel->id,
            'contact_phone' => '12345',
            'conversation_type' => 'service',
            'cost_cents' => 5,
        ]);

        $this->assertTrue($window->expires_at->isAfter(now()->addHours(23)));
    }

    public function test_purge_expired_removes_old_windows(): void
    {
        $channel = LeoChannel::factory()->create();

        // Expired
        WhatsAppConversationWindow::create([
            'channel_id' => $channel->id,
            'contact_phone' => '1',
            'conversation_type' => 'service',
            'opened_at' => now()->subHours(30),
            'expires_at' => now()->subHours(6),
            'cost_cents' => 5,
        ]);

        // Active
        WhatsAppConversationWindow::create([
            'channel_id' => $channel->id,
            'contact_phone' => '2',
            'conversation_type' => 'service',
            'opened_at' => now(),
            'expires_at' => now()->addHours(24),
            'cost_cents' => 5,
        ]);

        $count = $this->tracker->purgeExpired();

        $this->assertEquals(1, $count);
        $this->assertDatabaseCount('whatsapp_conversation_windows', 1);
        $this->assertDatabaseHas('whatsapp_conversation_windows', ['contact_phone' => '2']);
    }
}

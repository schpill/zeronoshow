<?php

namespace Tests\Feature\Leo;

use App\Models\Business;
use App\Models\LeoChannel;
use App\Services\Leo\LeoGeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeoWebhookWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config([
            'services.whatsapp.phone_number_id' => '12345',
            'services.whatsapp.access_token' => 'test_token',
        ]);
    }

    public function test_whatsapp_verify_webhook(): void
    {
        config(['services.whatsapp.verify_token' => 'test_token']);

        $response = $this->getJson('/api/v1/webhooks/leo/whatsapp?hub_mode=subscribe&hub_verify_token=test_token&hub_challenge=12345');

        $response->assertStatus(200);
        $this->assertEquals('12345', $response->getContent());
    }

    public function test_whatsapp_inbound_message_with_sufficient_credit(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([], 200),
        ]);

        $business = Business::factory()->create([
            'whatsapp_credit_cents' => 1000,
            'leo_addon_active' => true,
        ]);

        $channel = LeoChannel::factory()->create([
            'business_id' => $business->id,
            'channel' => 'whatsapp',
            'external_identifier' => '33688888888',
            'is_active' => true,
        ]);

        $this->mock(LeoGeminiService::class, function ($mock) {
            $mock->shouldReceive('ask')->andReturn('Hello from AI');
        });

        $payload = [
            'entry' => [['changes' => [['value' => ['messages' => [['from' => '33688888888', 'text' => ['body' => 'Bonjour']]]]]]]],
        ];

        $response = $this->postJson('/api/v1/webhooks/leo/whatsapp', $payload);

        $response->assertStatus(200);

        // Check credit deducted (default 5 cents)
        $this->assertEquals(995, $business->fresh()->whatsapp_credit_cents);

        // Check window opened
        $this->assertDatabaseHas('whatsapp_conversation_windows', [
            'channel_id' => $channel->id,
            'contact_phone' => '33688888888',
        ]);

        // Check message logged
        $this->assertDatabaseHas('leo_message_logs', [
            'channel_id' => $channel->id,
            'direction' => 'inbound',
            'raw_message' => 'Bonjour',
        ]);
    }

    public function test_whatsapp_inbound_message_with_insufficient_credit(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([], 200),
        ]);

        $business = Business::factory()->create([
            'whatsapp_credit_cents' => 0,
            'leo_addon_active' => true,
        ]);

        LeoChannel::factory()->create([
            'business_id' => $business->id,
            'channel' => 'whatsapp',
            'external_identifier' => '33688888888',
            'is_active' => false,
        ]);

        $payload = [
            'entry' => [['changes' => [['value' => ['messages' => [['from' => '33688888888', 'text' => ['body' => 'Bonjour']]]]]]]],
        ];

        $response = $this->postJson('/api/v1/webhooks/leo/whatsapp', $payload);

        $response->assertStatus(200);

        // Check error message sent to user
        Http::assertSent(function ($request) {
            return str_contains($request['text']['body'], 'épuisé');
        });
    }
}

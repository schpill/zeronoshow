<?php

namespace Tests\Feature\Leo;

use App\Models\Business;
use App\Models\LeoSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_starts_a_pending_selection_flow_for_multi_business_senders(): void
    {
        config()->set('services.telegram.token', 'telegram-token');
        config()->set('services.telegram.webhook_secret', 'telegram-secret');

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $firstBusiness = Business::factory()->create([
            'name' => 'Salon République',
            'leo_addon_active' => true,
        ]);
        $secondBusiness = Business::factory()->create([
            'name' => 'Salon Bastille',
            'leo_addon_active' => true,
        ]);

        $firstChannel = $firstBusiness->leoChannel()->create([
            'channel' => 'telegram',
            'external_identifier' => '123456789',
            'bot_name' => 'Léo République',
            'is_active' => true,
        ]);
        $secondBusiness->leoChannel()->create([
            'channel' => 'telegram',
            'external_identifier' => '123456789',
            'bot_name' => 'Léo Bastille',
            'is_active' => true,
        ]);

        $this->telegramWebhook('Bonjour')
            ->assertOk()
            ->assertExactJson(['received' => true]);

        $this->assertDatabaseHas('leo_sessions', [
            'channel_id' => $firstChannel->id,
            'sender_identifier' => '123456789',
            'active_business_id' => null,
            'pending_selection' => true,
        ]);

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/sendMessage')
                && str_contains((string) $request['text'], 'Pour quel établissement ?')
                && str_contains((string) $request['text'], 'Salon République')
                && str_contains((string) $request['text'], 'Salon Bastille');
        });
    }

    public function test_it_resolves_a_pending_selection_before_asking_gemini(): void
    {
        config()->set('services.telegram.token', 'telegram-token');
        config()->set('services.telegram.webhook_secret', 'telegram-secret');
        config()->set('services.gemini.api_key', 'gemini-key');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => 'Voici le résumé demandé.',
                        ]],
                    ],
                ]],
            ], 200),
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $firstBusiness = Business::factory()->create([
            'name' => 'Salon République',
            'leo_addon_active' => true,
        ]);
        $secondBusiness = Business::factory()->create([
            'name' => 'Salon Bastille',
            'leo_addon_active' => true,
        ]);

        $firstChannel = $firstBusiness->leoChannel()->create([
            'channel' => 'telegram',
            'external_identifier' => '123456789',
            'bot_name' => 'Léo République',
            'is_active' => true,
        ]);
        $secondChannel = $secondBusiness->leoChannel()->create([
            'channel' => 'telegram',
            'external_identifier' => '123456789',
            'bot_name' => 'Léo Bastille',
            'is_active' => true,
        ]);

        LeoSession::query()->create([
            'channel_id' => $firstChannel->id,
            'sender_identifier' => '123456789',
            'active_business_id' => null,
            'pending_selection' => true,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->telegramWebhook('2')
            ->assertOk()
            ->assertExactJson(['received' => true]);

        $this->assertDatabaseMissing('leo_sessions', [
            'channel_id' => $firstChannel->id,
            'sender_identifier' => '123456789',
            'pending_selection' => true,
        ]);

        $this->assertDatabaseHas('leo_sessions', [
            'channel_id' => $secondChannel->id,
            'sender_identifier' => '123456789',
            'active_business_id' => $secondBusiness->id,
            'pending_selection' => false,
        ]);

        Http::assertSent(function ($request) use ($secondBusiness): bool {
            if (! str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                return false;
            }

            return str_contains(json_encode($request->data(), JSON_THROW_ON_ERROR), $secondBusiness->name);
        });

        $this->assertDatabaseHas('leo_message_logs', [
            'channel_id' => $secondChannel->id,
            'direction' => 'inbound',
            'raw_message' => '2',
        ]);

        $this->assertDatabaseHas('leo_message_logs', [
            'channel_id' => $secondChannel->id,
            'direction' => 'outbound',
            'raw_message' => 'Voici le résumé demandé.',
        ]);
    }

    private function telegramWebhook(string $text)
    {
        return $this->postJson(
            '/api/v1/webhooks/leo/telegram',
            [
                'message' => [
                    'text' => $text,
                    'from' => [
                        'id' => 123456789,
                    ],
                ],
            ],
            [
                'X-Telegram-Bot-Api-Secret-Token' => 'telegram-secret',
            ],
        );
    }
}

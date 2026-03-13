<?php

namespace Tests\Unit\Leo;

use App\Services\Leo\TelegramChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class TelegramChannelTest extends TestCase
{
    public function test_parse_inbound_returns_a_message_for_a_valid_payload(): void
    {
        $channel = new TelegramChannel;
        $request = Request::create('/', 'POST', server: [], content: json_encode([
            'message' => [
                'text' => 'Bonjour Léo',
                'from' => ['id' => 123456789],
            ],
        ], JSON_THROW_ON_ERROR));

        $message = $channel->parseInbound($request);

        $this->assertNotNull($message);
        $this->assertSame('telegram', $message->channelType);
        $this->assertSame('123456789', $message->senderId);
        $this->assertSame('Bonjour Léo', $message->messageText);
    }

    public function test_parse_inbound_returns_null_when_payload_is_missing_text(): void
    {
        $channel = new TelegramChannel;
        $request = Request::create('/', 'POST', server: [], content: json_encode([
            'message' => [
                'from' => ['id' => 123456789],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->assertNull($channel->parseInbound($request));
    }

    public function test_verify_webhook_requires_a_matching_secret(): void
    {
        config()->set('services.telegram.webhook_secret', 'leo-secret');
        $channel = new TelegramChannel;

        $validRequest = Request::create('/', 'POST', server: [
            'HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => 'leo-secret',
        ]);
        $invalidRequest = Request::create('/', 'POST', server: [
            'HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => 'wrong-secret',
        ]);

        $this->assertTrue($channel->verifyWebhook($validRequest));
        $this->assertFalse($channel->verifyWebhook($invalidRequest));
    }

    public function test_send_message_posts_to_telegram_api(): void
    {
        config()->set('services.telegram.token', 'telegram-token');
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $channel = new TelegramChannel;
        $channel->sendMessage('123456789', 'Bonjour');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.telegram.org/bottelegram-token/sendMessage'
                && $request['chat_id'] === '123456789'
                && $request['text'] === 'Bonjour';
        });
    }

    public function test_send_message_throws_when_telegram_rejects_the_request(): void
    {
        config()->set('services.telegram.token', 'telegram-token');
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => false], 500),
        ]);

        $channel = new TelegramChannel;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Telegram delivery failed.');

        $channel->sendMessage('123456789', 'Bonjour');
    }
}

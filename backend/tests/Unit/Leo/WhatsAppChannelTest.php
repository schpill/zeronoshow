<?php

namespace Tests\Unit\Leo;

use App\Exceptions\LeoChannelException;
use App\Leo\Tools\WhatsAppChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppChannelTest extends TestCase
{
    private WhatsAppChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->channel = new WhatsAppChannel;

        config([
            'services.whatsapp.phone_number_id' => '12345',
            'services.whatsapp.access_token' => 'secret_token',
            'services.whatsapp.verify_token' => 'my_verify_token',
        ]);
    }

    public function test_send_message_success(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messaging_product' => 'whatsapp'], 200),
        ]);

        $this->channel->sendMessage('33612345678', 'Hello from Léo');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v20.0/12345/messages' &&
                   $request['to'] === '33612345678' &&
                   $request['text']['body'] === 'Hello from Léo';
        });
    }

    public function test_send_message_failure_throws_exception(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => 'bad request'], 400),
        ]);

        $this->expectException(LeoChannelException::class);
        $this->channel->sendMessage('33612345678', 'Hello');
    }

    public function test_parse_inbound_valid_payload(): void
    {
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => '33688888888',
                                        'text' => ['body' => 'Salut Léo'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $message = $this->channel->parseInbound($request);

        $this->assertNotNull($message);
        $this->assertEquals('whatsapp', $message->channelType);
        $this->assertEquals('33688888888', $message->senderId);
        $this->assertEquals('Salut Léo', $message->messageText);
    }

    public function test_parse_inbound_invalid_payload_returns_null(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['foo' => 'bar']));
        $this->assertNull($this->channel->parseInbound($request));
    }

    public function test_verify_webhook_success(): void
    {
        $request = new Request([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'my_verify_token',
        ]);

        $this->assertTrue($this->channel->verifyWebhook($request));
    }

    public function test_verify_webhook_failure(): void
    {
        $request = new Request([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wrong_token',
        ]);

        $this->assertFalse($this->channel->verifyWebhook($request));
    }
}

<?php

namespace App\Services\Leo;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramChannel implements LeoChannelInterface
{
    public function sendMessage(string $recipientId, string $text): void
    {
        $token = (string) config('services.telegram.token');

        $response = Http::baseUrl("https://api.telegram.org/bot{$token}")
            ->post('/sendMessage', [
                'chat_id' => $recipientId,
                'text' => $text,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Telegram delivery failed.');
        }
    }

    public function parseInbound(Request $request): ?LeoInboundMessage
    {
        $payload = $request->json()->all();
        $text = data_get($payload, 'message.text');
        $senderId = data_get($payload, 'message.from.id');

        if (! is_string($text) || $text === '' || $senderId === null) {
            return null;
        }

        return new LeoInboundMessage(
            'telegram',
            (string) $senderId,
            $text,
            $payload,
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        $expected = (string) config('services.telegram.webhook_secret');
        $provided = (string) $request->header('X-Telegram-Bot-Api-Secret-Token');

        if ($expected === '' || $provided === '') {
            return false;
        }

        return hash_equals($expected, $provided);
    }
}

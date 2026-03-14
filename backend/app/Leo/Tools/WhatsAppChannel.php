<?php

namespace App\Leo\Tools;

use App\Exceptions\LeoChannelException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel implements LeoChannelInterface
{
    public function sendMessage(string $recipientId, string $text): void
    {
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');
        $accessToken = (string) config('services.whatsapp.access_token');

        if ($phoneNumberId === '' || $accessToken === '') {
            throw new LeoChannelException('WhatsApp configuration is missing.');
        }

        $response = Http::withToken($accessToken)
            ->post("https://graph.facebook.com/v20.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $recipientId,
                'type' => 'text',
                'text' => ['body' => $text],
            ]);

        if (! $response->successful()) {
            Log::error('WhatsApp delivery failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'recipient' => $recipientId,
            ]);
            throw new LeoChannelException('WhatsApp delivery failed: '.$response->body());
        }
    }

    public function parseInbound(Request $request): ?LeoInboundMessage
    {
        $payload = $request->json()->all();

        // Meta Cloud API structure: entry[0].changes[0].value.messages[0]
        $entry = data_get($payload, 'entry.0');
        $change = data_get($entry, 'changes.0');
        $value = data_get($change, 'value');
        $message = data_get($value, 'messages.0');

        if (! $message) {
            return null;
        }

        $text = data_get($message, 'text.body');
        $senderId = data_get($message, 'from');

        if (! is_string($text) || $text === '' || ! $senderId) {
            return null;
        }

        return new LeoInboundMessage(
            'whatsapp',
            (string) $senderId,
            $text,
            $payload,
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $expectedToken = config('services.whatsapp.verify_token');

        return $mode === 'subscribe' && $token === $expectedToken;
    }
}

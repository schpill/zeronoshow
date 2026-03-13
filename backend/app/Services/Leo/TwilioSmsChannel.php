<?php

namespace App\Services\Leo;

use Illuminate\Http\Request;
use RuntimeException;

class TwilioSmsChannel implements LeoChannelInterface
{
    public function sendMessage(string $recipientId, string $text): void
    {
        throw new RuntimeException('SMS Leo channel is not implemented yet.');
    }

    public function parseInbound(Request $request): ?LeoInboundMessage
    {
        return null;
    }

    public function verifyWebhook(Request $request): bool
    {
        return false;
    }
}

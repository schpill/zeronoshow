<?php

namespace App\Services\Leo;

use Illuminate\Http\Request;

interface LeoChannelInterface
{
    public function sendMessage(string $recipientId, string $text): void;

    public function parseInbound(Request $request): ?LeoInboundMessage;

    public function verifyWebhook(Request $request): bool;
}

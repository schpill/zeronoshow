<?php

namespace App\Services\Leo;

readonly class LeoInboundMessage
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public string $channelType,
        public string $senderId,
        public string $messageText,
        public array $rawPayload,
    ) {}
}

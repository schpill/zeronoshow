<?php

namespace App\Services\Leo;

use App\Models\Business;
use App\Models\LeoSession;

class LeoSessionService
{
    public function resolve(string $channelId, string $senderId): ?Business
    {
        $session = LeoSession::query()
            ->where('channel_id', $channelId)
            ->forSender($senderId)
            ->valid()
            ->with('activeBusiness')
            ->first();

        /** @var Business|null $business */
        $business = $session?->activeBusiness;

        return $business;
    }

    public function set(string $channelId, string $senderId, ?string $businessId, int $ttlSeconds = 300): void
    {
        LeoSession::query()->updateOrCreate(
            [
                'channel_id' => $channelId,
                'sender_identifier' => $senderId,
            ],
            [
                'active_business_id' => $businessId,
                'expires_at' => now()->addSeconds($ttlSeconds),
            ],
        );
    }

    public function clear(string $channelId, string $senderId): void
    {
        LeoSession::query()
            ->where('channel_id', $channelId)
            ->forSender($senderId)
            ->delete();
    }
}

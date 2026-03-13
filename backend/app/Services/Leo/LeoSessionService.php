<?php

namespace App\Services\Leo;

use App\Models\Business;
use App\Models\LeoChannel;
use App\Models\LeoSession;
use Illuminate\Support\Collection;

class LeoSessionService
{
    public function resolve(string $channelId, string $senderId): ?Business
    {
        $this->purgeExpired();

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

    public function set(
        string $channelId,
        string $senderId,
        ?string $businessId,
        int $ttlSeconds = 300,
        bool $pendingSelection = false,
    ): void {
        $this->purgeExpired();

        $existingSession = LeoSession::query()
            ->where('channel_id', $channelId)
            ->forSender($senderId)
            ->valid()
            ->first();

        if (
            $existingSession
            && $existingSession->active_business_id === $businessId
            && $existingSession->pending_selection === $pendingSelection
        ) {
            return;
        }

        LeoSession::query()->updateOrCreate(
            [
                'channel_id' => $channelId,
                'sender_identifier' => $senderId,
            ],
            [
                'active_business_id' => $businessId,
                'pending_selection' => $pendingSelection,
                'expires_at' => now()->addSeconds($ttlSeconds),
            ],
        );
    }

    /**
     * @param  Collection<int, LeoChannel>  $channels
     */
    public function findPendingSelection(Collection $channels, string $senderId): ?LeoSession
    {
        $this->purgeExpired();

        $channelIds = $channels->pluck('id');

        if ($channelIds->isEmpty()) {
            return null;
        }

        return LeoSession::query()
            ->whereIn('channel_id', $channelIds)
            ->forSender($senderId)
            ->valid()
            ->where('pending_selection', true)
            ->first();
    }

    /**
     * @param  Collection<int, LeoChannel>  $channels
     */
    public function clearPendingSelections(Collection $channels, string $senderId): void
    {
        $this->purgeExpired();

        $channelIds = $channels->pluck('id');

        if ($channelIds->isEmpty()) {
            return;
        }

        LeoSession::query()
            ->whereIn('channel_id', $channelIds)
            ->forSender($senderId)
            ->where('pending_selection', true)
            ->delete();
    }

    public function clear(string $channelId, string $senderId): void
    {
        LeoSession::query()
            ->where('channel_id', $channelId)
            ->forSender($senderId)
            ->delete();
    }

    public function purgeExpired(): int
    {
        return LeoSession::query()
            ->where('expires_at', '<=', now())
            ->delete();
    }
}

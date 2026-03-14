<?php

namespace App\Services\Leo;

use App\Models\LeoChannel;
use Illuminate\Support\Collection;

class LeoBusinessResolver
{
    public function __construct(
        private readonly LeoSessionService $sessionService,
    ) {}

    /**
     * @return array{status: 'none'|'single'|'multiple', channel: LeoChannel|null, channels: Collection<int, LeoChannel>}
     */
    public function resolve(string $channelType, string $senderId): array
    {
        $channels = LeoChannel::query()
            ->with('business')
            ->where('channel', $channelType)
            ->where('external_identifier', $senderId)
            ->when($channelType !== 'whatsapp', fn ($query) => $query->where('is_active', true))
            ->whereHas('business', fn ($query) => $query->where('leo_addon_active', true))
            ->get();

        if ($channels->isEmpty()) {
            return ['status' => 'none', 'channel' => null, 'channels' => collect()];
        }

        if ($channels->count() === 1) {
            return ['status' => 'single', 'channel' => $channels->first(), 'channels' => $channels];
        }

        $sessionMatch = $channels->first(function (LeoChannel $channel) use ($senderId): bool {
            $business = $this->sessionService->resolve($channel->id, $senderId);

            return $business !== null && $business->id === $channel->business_id;
        });

        if ($sessionMatch) {
            return ['status' => 'single', 'channel' => $sessionMatch, 'channels' => $channels];
        }

        return ['status' => 'multiple', 'channel' => null, 'channels' => $channels];
    }
}

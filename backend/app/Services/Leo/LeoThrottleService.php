<?php

namespace App\Services\Leo;

use Illuminate\Support\Facades\Cache;

class LeoThrottleService
{
    public function increment(string $identifier): int
    {
        $key = $this->cacheKey($identifier);
        Cache::add($key, 0, now()->addHour());

        return (int) Cache::increment($key);
    }

    public function isThrottled(string $identifier): bool
    {
        return (int) Cache::get($this->cacheKey($identifier), 0) >= $this->maxMessagesPerHour();
    }

    private function cacheKey(string $identifier): string
    {
        return 'leo:throttle:'.$identifier;
    }

    private function maxMessagesPerHour(): int
    {
        return max(1, (int) config('leo.throttle.max_messages_per_hour', 20));
    }
}

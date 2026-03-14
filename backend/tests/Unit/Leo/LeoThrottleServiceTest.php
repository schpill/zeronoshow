<?php

namespace Tests\Unit\Leo;

use App\Leo\Tools\LeoThrottleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LeoThrottleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_not_throttled_below_threshold(): void
    {
        config()->set('leo.throttle.max_messages_per_hour', 3);

        $service = app(LeoThrottleService::class);
        $identifier = 'telegram:123:test-under-threshold:'.random_int(1000, 999999);

        $service->increment($identifier);
        $service->increment($identifier);

        $this->assertFalse($service->isThrottled($identifier));
    }

    public function test_it_is_throttled_at_threshold(): void
    {
        config()->set('leo.throttle.max_messages_per_hour', 2);

        $service = app(LeoThrottleService::class);
        $identifier = 'telegram:123:test-at-threshold:'.random_int(1000, 999999);

        $service->increment($identifier);
        $service->increment($identifier);

        $this->assertTrue($service->isThrottled($identifier));
    }

    public function test_it_expires_counters_after_one_hour(): void
    {
        config()->set('cache.default', 'array');
        config()->set('leo.throttle.max_messages_per_hour', 1);

        $service = app(LeoThrottleService::class);
        $identifier = 'telegram:123:test-expiry:'.random_int(1000, 999999);

        $service->increment($identifier);

        Cache::forget('leo:throttle:'.$identifier);

        $this->assertFalse($service->isThrottled($identifier));
    }

    public function test_it_uses_atomic_cache_operations_when_incrementing(): void
    {
        $service = app(LeoThrottleService::class);
        $identifier = 'telegram:123:test-expiration-metadata:'.random_int(1000, 999999);
        $key = 'leo:throttle:'.$identifier;

        Cache::shouldReceive('add')
            ->once()
            ->with($key, 0, \Mockery::type(\DateTimeInterface::class));
        Cache::shouldReceive('increment')
            ->once()
            ->with($key)
            ->andReturn(1);

        $this->assertSame(1, $service->increment($identifier));
    }
}

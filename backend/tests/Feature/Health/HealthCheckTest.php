<?php

namespace Tests\Feature\Health;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_ok_when_dependencies_are_healthy(): void
    {
        Redis::shouldReceive('ping')->once()->andReturn('PONG');
        config()->set('queue.default', 'database');

        $response = $this->getJson('/api/v1/health');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('db', 'ok')
            ->assertJsonPath('redis', 'ok')
            ->assertJsonPath('queue', 'ok');
    }

    public function test_it_returns_degraded_when_redis_is_unavailable(): void
    {
        Redis::shouldReceive('ping')->once()->andThrow(new \RuntimeException('redis down'));

        $response = $this->getJson('/api/v1/health');

        $response
            ->assertStatus(503)
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('redis', 'error');
    }
}

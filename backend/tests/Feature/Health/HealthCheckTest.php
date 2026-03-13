<?php

namespace Tests\Feature\Health;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_ok_when_dependencies_are_healthy(): void
    {
        Redis::shouldReceive('ping')->once()->andReturn('PONG');
        Redis::shouldReceive('smembers')
            ->once()
            ->with(sprintf('%ssupervisors', config('horizon.prefix')))
            ->andReturn(['supervisor-1']);
        config()->set('queue.default', 'redis');

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

    public function test_it_returns_degraded_when_no_queue_workers_are_registered(): void
    {
        Redis::shouldReceive('ping')->once()->andReturn('PONG');
        Redis::shouldReceive('smembers')
            ->once()
            ->with(sprintf('%ssupervisors', config('horizon.prefix')))
            ->andReturn([]);
        config()->set('queue.default', 'redis');

        $response = $this->getJson('/api/v1/health');

        $response
            ->assertStatus(503)
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('queue', 'error');
    }

    public function test_it_returns_degraded_when_the_database_is_unavailable(): void
    {
        DB::shouldReceive('connection')->once()->andThrow(new \RuntimeException('db down'));
        Redis::shouldReceive('ping')->once()->andReturn('PONG');
        Redis::shouldReceive('smembers')
            ->once()
            ->with(sprintf('%ssupervisors', config('horizon.prefix')))
            ->andReturn(['supervisor-1']);
        config()->set('queue.default', 'redis');

        $response = $this->getJson('/api/v1/health');

        $response
            ->assertStatus(503)
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('db', 'error');
    }
}

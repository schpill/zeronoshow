<?php

namespace Tests\Feature\Commands;

use App\Models\SmsLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RunSmokeTestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_runs_the_phase_four_smoke_checks(): void
    {
        config(['queue.default' => 'redis']);

        Redis::shouldReceive('ping')->once()->andReturn('PONG');
        Redis::shouldReceive('smembers')
            ->once()
            ->with(sprintf('%ssupervisors', config('horizon.prefix')))
            ->andReturn(['supervisor-1']);

        $this->artisan('smoke:test')
            ->expectsOutputToContain('Smoke tests passed')
            ->assertExitCode(0);

        $this->assertDatabaseCount('sms_logs', 0);
        $this->assertSame(0, SmsLog::query()->count());
    }
}

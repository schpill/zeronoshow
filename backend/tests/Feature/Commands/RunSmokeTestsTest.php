<?php

namespace Tests\Feature\Commands;

use App\Jobs\SendVerificationSms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RunSmokeTestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_runs_the_phase_four_smoke_checks(): void
    {
        Queue::fake();
        Redis::shouldReceive('ping')->once()->andReturn('PONG');

        $this->artisan('smoke:test')
            ->expectsOutputToContain('Smoke tests passed')
            ->assertExitCode(0);

        Queue::assertPushed(SendVerificationSms::class);
    }
}

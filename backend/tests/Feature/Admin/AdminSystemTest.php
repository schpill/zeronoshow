<?php

use App\Models\Admin;
use App\Models\SmsLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('health endpoint returns all fields', function () {
    $admin = Admin::factory()->create();

    DB::table('failed_jobs')->insert([
        'uuid' => (string) str()->uuid(),
        'connection' => 'redis',
        'queue' => 'default',
        'payload' => '{}',
        'exception' => 'Failed',
        'failed_at' => now(),
    ]);

    SmsLog::factory()->create([
        'twilio_sid' => 'SM123',
        'created_at' => now()->subMinutes(5),
    ]);

    Redis::shouldReceive('exists')->once()->with('znz:worker:heartbeat')->andReturn(1);
    Redis::shouldReceive('ping')->once()->andReturn('PONG');

    Sanctum::actingAs($admin, ['admin']);

    $this->getJson('/api/v1/admin/system/health')
        ->assertOk()
        ->assertJsonPath('queue_worker_running', true)
        ->assertJsonPath('failed_jobs_count', 1)
        ->assertJsonPath('redis_ping', true)
        ->assertJsonPath('database_ok', true)
        ->assertJsonPath('last_twilio_webhook_at', now()->subMinutes(5)->toIso8601String());
});

it('redis ping failure is reported gracefully', function () {
    $admin = Admin::factory()->create();

    Redis::shouldReceive('exists')->once()->with('znz:worker:heartbeat')->andReturn(0);
    Redis::shouldReceive('ping')->once()->andThrow(new RuntimeException('Redis unavailable'));

    Sanctum::actingAs($admin, ['admin']);

    $this->getJson('/api/v1/admin/system/health')
        ->assertOk()
        ->assertJsonPath('queue_worker_running', false)
        ->assertJsonPath('redis_ping', false)
        ->assertJsonPath('database_ok', true)
        ->assertJsonPath('last_twilio_webhook_at', null);
});

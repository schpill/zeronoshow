<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $dbStatus = $this->checkDatabase();
        $redisStatus = $this->checkRedis();
        $queueStatus = $this->checkQueue();

        $payload = [
            'status' => in_array('error', [$dbStatus, $redisStatus, $queueStatus], true) ? 'degraded' : 'ok',
            'db' => $dbStatus,
            'redis' => $redisStatus,
            'queue' => $queueStatus,
            'version' => (string) config('app.version'),
        ];

        return response()->json($payload, $payload['status'] === 'ok' ? 200 : 503);
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();

            return 'ok';
        } catch (\Throwable) {
            return 'error';
        }
    }

    private function checkRedis(): string
    {
        try {
            Redis::ping();

            return 'ok';
        } catch (\Throwable) {
            return 'error';
        }
    }

    private function checkQueue(): string
    {
        if (config('queue.default') !== 'redis') {
            return 'error';
        }

        try {
            $supervisors = Redis::smembers(sprintf('%ssupervisors', config('horizon.prefix')));

            return count($supervisors) > 0 ? 'ok' : 'error';
        } catch (\Throwable) {
            return 'error';
        }
    }
}

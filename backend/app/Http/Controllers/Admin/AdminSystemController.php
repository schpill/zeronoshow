<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use OpenApi\Attributes as OA;
use Throwable;

#[OA\Tag(
    name: 'Admin System',
    description: 'Operational health endpoints for the admin dashboard',
)]
class AdminSystemController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/system/health',
        tags: ['Admin System'],
        summary: 'Return queue, database and Redis health indicators',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Health payload'),
        ],
    )]
    public function health(): JsonResponse
    {
        return response()->json([
            'queue_worker_running' => $this->queueWorkerRunning(),
            'failed_jobs_count' => DB::table('failed_jobs')->count(),
            'redis_ping' => $this->redisPing(),
            'last_twilio_webhook_at' => $this->lastTwilioWebhookAt(),
            'database_ok' => $this->databaseOk(),
        ]);
    }

    private function queueWorkerRunning(): bool
    {
        try {
            return (bool) Redis::exists('znz:worker:heartbeat');
        } catch (Throwable) {
            return false;
        }
    }

    private function redisPing(): bool
    {
        try {
            Redis::ping();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function lastTwilioWebhookAt(): ?string
    {
        $timestamp = DB::table('sms_logs')
            ->whereNotNull('twilio_sid')
            ->max('created_at');

        if ($timestamp === null) {
            return null;
        }

        return CarbonImmutable::parse($timestamp)->toIso8601String();
    }

    private function databaseOk(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}

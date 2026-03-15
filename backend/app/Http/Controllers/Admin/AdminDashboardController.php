<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Admin Dashboard',
    description: 'Platform KPI endpoints for the operator dashboard',
)]
class AdminDashboardController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/stats',
        tags: ['Admin Dashboard'],
        summary: 'Return platform KPI stats',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Stats payload'),
        ],
    )]
    public function stats(): JsonResponse
    {
        $startOfMonth = now()->startOfMonth();

        $stats = DB::table('businesses')
            ->selectRaw('COUNT(*) as total_businesses')
            ->selectRaw("SUM(CASE WHEN subscription_status = 'trial' AND trial_ends_at > ? THEN 1 ELSE 0 END) as active_trials", [now()])
            ->selectRaw("SUM(CASE WHEN subscription_status = 'trial' AND trial_ends_at <= ? THEN 1 ELSE 0 END) as expired_trials", [now()])
            ->selectRaw("SUM(CASE WHEN subscription_status = 'active' THEN 1 ELSE 0 END) as paid_subscriptions")
            ->selectRaw("SUM(CASE WHEN subscription_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_subscriptions")
            ->selectRaw('(SELECT COUNT(*) FROM sms_logs WHERE created_at >= ?) as sms_sent_this_month', [$startOfMonth])
            ->selectRaw('(SELECT COALESCE(SUM(cost_eur), 0) FROM sms_logs WHERE created_at >= ?) as sms_cost_this_month', [$startOfMonth])
            ->selectRaw('(SELECT COUNT(*) FROM failed_jobs) as failed_jobs_count')
            ->first();

        return response()->json([
            'total_businesses' => (int) ($stats->total_businesses ?? 0),
            'active_trials' => (int) ($stats->active_trials ?? 0),
            'expired_trials' => (int) ($stats->expired_trials ?? 0),
            'paid_subscriptions' => (int) ($stats->paid_subscriptions ?? 0),
            'cancelled_subscriptions' => (int) ($stats->cancelled_subscriptions ?? 0),
            'sms_sent_this_month' => (int) ($stats->sms_sent_this_month ?? 0),
            'sms_cost_this_month' => round((float) ($stats->sms_cost_this_month ?? 0), 4),
            'failed_jobs_count' => (int) ($stats->failed_jobs_count ?? 0),
        ]);
    }
}

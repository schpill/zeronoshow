<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReservationResource;
use App\Models\LeoMessageLog;
use App\Models\Reservation;
use App\Models\SmsLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $business = $request->user();
        $date = $request->query('date');
        $week = $request->query('week');
        $version = (int) Cache::get("dashboard_version:{$business->id}", 1);
        $cacheKey = sprintf(
            'dashboard:%s:%d:%s:%s',
            $business->id,
            $version,
            $date ?? 'today',
            $week ?? 'day',
        );

        $payload = Cache::remember($cacheKey, 30, function () use ($business, $date, $week): array {
            $selectedDay = $date
                ? Carbon::parse((string) $date, $business->timezone)
                : now($business->timezone);

            $query = Reservation::query()
                ->with('customer')
                ->withCount('smsLogs')
                ->where('business_id', $business->id)
                ->orderBy('scheduled_at');

            if ($week) {
                [$year, $weekNumber] = explode('-W', (string) $week);
                $start = now($business->timezone)->setISODate((int) $year, (int) $weekNumber)->startOfWeek();
                $end = $start->copy()->endOfWeek();
                $query->whereBetween('scheduled_at', [$start->utc(), $end->utc()]);
            } else {
                $query->whereBetween('scheduled_at', [
                    $selectedDay->copy()->startOfDay()->utc(),
                    $selectedDay->copy()->endOfDay()->utc(),
                ]);
            }

            $reservations = $query->get();

            $weeklyReservations = Reservation::query()
                ->where('business_id', $business->id)
                ->whereBetween('scheduled_at', [
                    $selectedDay->copy()->subDays(6)->startOfDay()->utc(),
                    $selectedDay->copy()->endOfDay()->utc(),
                ])
                ->whereIn('status', ['show', 'no_show'])
                ->get(['status']);

            $weeklyTotal = $weeklyReservations->count();
            $weeklyNoShows = $weeklyReservations->where('status', 'no_show')->count();

            return [
                'reservations' => ReservationResource::collection($reservations)->resolve(),
                'stats' => [
                    'confirmed' => $reservations->where('status', 'confirmed')->count(),
                    'pending_verification' => $reservations->where('status', 'pending_verification')->count(),
                    'pending_reminder' => $reservations->where('status', 'pending_reminder')->count(),
                    'cancelled' => $reservations
                        ->whereIn('status', ['cancelled_by_client', 'cancelled_no_confirmation'])
                        ->count(),
                    'no_show' => $reservations->where('status', 'no_show')->count(),
                    'show' => $reservations->where('status', 'show')->count(),
                    'total' => $reservations->count(),
                ],
                'sms_cost_this_month' => $this->monthlySmsCost($business->id, $selectedDay),
                'weekly_no_show_rate' => $weeklyTotal > 0
                    ? (float) round(($weeklyNoShows / $weeklyTotal) * 100, 1)
                    : 0.0,
                'leo_activity' => $this->latestLeoActivity($business->id),
            ];
        });

        return response()->json($payload, 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    private function monthlySmsCost(string $businessId, Carbon $referenceDate): float
    {
        return (float) SmsLog::query()
            ->where('business_id', $businessId)
            ->whereBetween('created_at', [
                $referenceDate->copy()->startOfMonth()->utc(),
                $referenceDate->copy()->endOfMonth()->utc(),
            ])
            ->sum('cost_eur');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function latestLeoActivity(string $businessId): array
    {
        return LeoMessageLog::query()
            ->select('leo_message_logs.*')
            ->join('leo_channels', 'leo_channels.id', '=', 'leo_message_logs.channel_id')
            ->where('leo_channels.business_id', $businessId)
            ->latest('leo_message_logs.created_at')
            ->limit(3)
            ->get()
            ->map(fn (LeoMessageLog $log): array => [
                'id' => $log->id,
                'direction' => $log->direction->value,
                'intent' => $log->intent,
                'response_preview' => $log->response_preview,
                'created_at' => optional($log->created_at)->toIso8601String(),
            ])
            ->all();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationStatusRequest;
use App\Http\Resources\ReservationResource;
use App\Http\Resources\SmsLogResource;
use App\Http\Resources\VoiceCallLogResource;
use App\Jobs\SendVerificationSms;
use App\Models\Customer;
use App\Models\Reservation;
use App\Services\ReliabilityScoreService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    public function store(StoreReservationRequest $request): JsonResponse
    {
        $business = $request->user();
        $scheduledAt = Carbon::parse($request->string('scheduled_at')->toString())->utc();
        $phoneVerified = (bool) $request->boolean('phone_verified');
        $minutesUntilReservation = max(0, now()->utc()->diffInMinutes($scheduledAt, false));

        $customer = Customer::query()->firstOrCreate(
            ['phone' => $request->string('phone')->toString()],
            [
                'reservations_count' => 0,
                'shows_count' => 0,
                'no_shows_count' => 0,
                'score_tier' => 'at_risk',
                'opted_out' => false,
                'is_blacklisted' => false,
                'is_vip' => false,
            ],
        );

        $customer->increment('reservations_count');

        $token = null;
        $expiresAt = null;
        $status = 'pending_verification';
        $warning = null;
        $verificationBody = null;

        if ($phoneVerified) {
            $token = (string) Str::uuid();
            $expiresAt = $scheduledAt->copy()->subMinutes(15);
            $status = 'pending_reminder';
        } elseif ($minutesUntilReservation < 30) {
            $warning = 'Appointment too soon for SMS confirmation';
        } elseif ($scheduledAt->greaterThan(now()->utc()->addMinutes(30))) {
            $token = (string) Str::uuid();
            $expiresAt = $scheduledAt->copy()->subMinutes(15);

            if ($minutesUntilReservation < 120) {
                $verificationBody = sprintf(
                    'Votre RDV est dans moins de 2h chez %s. Confirmez: %s',
                    $business->name,
                    route('confirmation.show', $token),
                );
            } else {
                $expiresAt = now()->utc()->addHours(24)->min($scheduledAt->copy()->subHours(2));
            }
        }

        $reservation = Reservation::query()->create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'customer_name' => $request->string('customer_name')->toString(),
            'scheduled_at' => $scheduledAt,
            'guests' => $request->integer('guests') ?: 1,
            'notes' => $request->input('notes'),
            'status' => $status,
            'phone_verified' => $phoneVerified,
            'confirmation_token' => $token,
            'token_expires_at' => $expiresAt,
            'status_changed_at' => now()->utc(),
        ])->load('customer');

        if (! $phoneVerified && $token !== null) {
            SendVerificationSms::dispatch($reservation->id, $verificationBody);
        }

        return response()->json([
            'reservation' => ReservationResource::make($reservation),
            'customer' => [
                'reliability_score' => $customer->reliability_score,
                'score_tier' => $customer->getScoreTier(),
                'opted_out' => $customer->opted_out,
                'is_blacklisted' => $customer->is_blacklisted,
            ],
            'warning' => $warning,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $business = $request->user();
        $date = $request->query('date');
        $week = $request->query('week');
        $cacheKey = sprintf('dashboard:%s:%s:%s', $business->id, $date ?? 'none', $week ?? 'none');

        $payload = Cache::remember($cacheKey, 30, function () use ($business, $date, $week): array {
            $query = Reservation::query()
                ->with('customer')
                ->withCount('smsLogs')
                ->where('business_id', $business->id)
                ->orderBy('scheduled_at');

            if ($week) {
                [$year, $weekNumber] = explode('-W', (string) $week);
                $start = now()->setISODate((int) $year, (int) $weekNumber)->startOfWeek();
                $end = $start->copy()->endOfWeek();
                $query->whereBetween('scheduled_at', [$start->utc(), $end->utc()]);
            } else {
                $day = $date ? Carbon::parse((string) $date) : now($business->timezone);
                $query->whereBetween('scheduled_at', [$day->copy()->startOfDay()->utc(), $day->copy()->endOfDay()->utc()]);
            }

            $reservations = $query->get();

            return [
                'reservations' => ReservationResource::collection($reservations)->resolve(),
                'stats' => [
                    'confirmed' => $reservations->where('status', 'confirmed')->count(),
                    'pending_verification' => $reservations->where('status', 'pending_verification')->count(),
                    'pending_reminder' => $reservations->where('status', 'pending_reminder')->count(),
                    'cancelled' => $reservations->whereIn('status', ['cancelled_by_client', 'cancelled_no_confirmation'])->count(),
                    'no_show' => $reservations->where('status', 'no_show')->count(),
                    'show' => $reservations->where('status', 'show')->count(),
                    'total' => $reservations->count(),
                ],
            ];
        });

        return response()->json($payload);
    }

    public function show(Request $request, Reservation $reservation): JsonResponse
    {
        abort_if($reservation->business_id !== $request->user()->id, 403);

        $reservation->load(['customer', 'smsLogs', 'voiceCallLogs'])
            ->loadCount(['smsLogs', 'voiceCallLogs']);

        return response()->json([
            'reservation' => ReservationResource::make($reservation),
            'customer' => [
                'phone' => $reservation->customer->phone,
                'reliability_score' => $reservation->customer->reliability_score,
                'score_tier' => $reservation->customer->getScoreTier(),
                'reservations_count' => $reservation->customer->reservations_count,
                'shows_count' => $reservation->customer->shows_count,
                'no_shows_count' => $reservation->customer->no_shows_count,
                'opted_out' => $reservation->customer->opted_out,
                'is_blacklisted' => $reservation->customer->is_blacklisted,
                'notes' => $reservation->customer->notes,
                'is_vip' => $reservation->customer->is_vip,
                'birthday_month' => $reservation->customer->birthday_month,
                'birthday_day' => $reservation->customer->birthday_day,
                'preferred_table_notes' => $reservation->customer->preferred_table_notes,
            ],
            'sms_logs' => SmsLogResource::collection($reservation->smsLogs),
            'voice_call_logs' => VoiceCallLogResource::collection($reservation->voiceCallLogs),
        ]);
    }

    public function updateStatus(
        UpdateReservationStatusRequest $request,
        Reservation $reservation,
    ): JsonResponse {
        abort_if($reservation->business_id !== $request->user()->id, 403);

        $reservation->update([
            'status' => $request->string('status')->toString(),
            'status_changed_at' => now()->utc(),
        ]);

        $reservation->refresh()->load('customer');
        $customer = $reservation->customer;
        $score = $this->calculateScore($customer);

        return response()->json([
            'reservation' => ReservationResource::make($reservation->load('customer')),
            'customer' => [
                'reliability_score' => $score,
                'score_tier' => ReliabilityScoreService::getTierForScore($score),
            ],
        ]);
    }

    private function calculateScore(Customer $customer): ?float
    {
        $total = $customer->shows_count + $customer->no_shows_count;

        if ($total === 0) {
            return null;
        }

        return round(($customer->shows_count / $total) * 100, 2);
    }
}

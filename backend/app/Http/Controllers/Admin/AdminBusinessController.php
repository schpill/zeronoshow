<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminBusinessResource;
use App\Http\Resources\ReservationResource;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Admin Businesses',
    description: 'Business administration endpoints for the operator backoffice',
)]
class AdminBusinessController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/businesses',
        tags: ['Admin Businesses'],
        summary: 'List businesses for the admin backoffice',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated businesses'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $sort = $request->string('sort')->toString() ?: 'created_at';
        $allowedSorts = ['created_at', 'name', 'subscription_status'];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $query = Business::query()
            ->withCount('reservations')
            ->withCount(['smsLogs as sms_sent_count'])
            ->withMax('reservations as last_reservation_at', 'scheduled_at');

        if ($request->filled('search')) {
            $search = mb_strtolower($request->string('search')->toString());

            $query->where(function ($builder) use ($search): void {
                $builder
                    ->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        if ($request->filled('status')) {
            $query->where('subscription_status', $request->string('status')->toString());
        }

        $query->orderBy($sort, $sort === 'created_at' ? 'desc' : 'asc');

        return AdminBusinessResource::collection($query->paginate(20))->response();
    }

    #[OA\Get(
        path: '/api/v1/admin/businesses/{business}',
        tags: ['Admin Businesses'],
        summary: 'Show one business with reservations and SMS summary',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'business', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Business detail'),
        ],
    )]
    public function show(Business $business): JsonResponse
    {
        $business->loadCount('reservations');
        $business->loadCount(['smsLogs as sms_sent_count']);

        $recentReservations = $business->reservations()
            ->with('customer')
            ->latest('scheduled_at')
            ->limit(10)
            ->get();

        $smsSummary = $business->smsLogs()
            ->selectRaw('COUNT(*) as total_sent')
            ->selectRaw("SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->selectRaw('COALESCE(SUM(cost_eur), 0) as cost')
            ->first();

        return response()->json([
            'business' => (new AdminBusinessResource($business))->toArray(request()),
            'recent_reservations' => ReservationResource::collection($recentReservations)->resolve(),
            'sms_log_summary' => [
                'total_sent' => (int) ($smsSummary->total_sent ?? 0),
                'delivered' => (int) ($smsSummary->delivered ?? 0),
                'failed' => (int) ($smsSummary->failed ?? 0),
                'cost' => round((float) ($smsSummary->cost ?? 0), 4),
            ],
            'subscription_history' => [[
                'subscription_status' => $business->subscription_status,
                'trial_ends_at' => $business->trial_ends_at?->toIso8601String(),
                'stripe_customer_id' => $business->stripe_customer_id,
                'stripe_subscription_id' => $business->stripe_subscription_id,
            ]],
        ]);
    }

    #[OA\Patch(
        path: '/api/v1/admin/businesses/{business}/extend-trial',
        tags: ['Admin Businesses'],
        summary: 'Extend a business trial period',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'business', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['days'],
                properties: [
                    new OA\Property(property: 'days', type: 'integer', minimum: 1, maximum: 90),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Trial extended'),
            new OA\Response(response: 422, description: 'Business is not in trial'),
        ],
    )]
    public function extendTrial(Request $request, Business $business): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:90'],
        ]);

        if ($business->subscription_status !== 'trial') {
            return response()->json([
                'message' => 'Business is not in trial.',
            ], 422);
        }

        $oldTrialEndsAt = $business->trial_ends_at;
        $newTrialEndsAt = $business->trial_ends_at?->copy()->addDays($validated['days']) ?? now()->addDays($validated['days']);

        $business->forceFill([
            'trial_ends_at' => $newTrialEndsAt,
        ])->save();

        $this->logAction($request, 'extend_trial', $business, [
            'days' => $validated['days'],
            'old_trial_ends_at' => $oldTrialEndsAt?->toIso8601String(),
            'new_trial_ends_at' => $newTrialEndsAt->toIso8601String(),
        ]);

        return response()->json((new AdminBusinessResource($business->fresh()))->toArray($request));
    }

    #[OA\Patch(
        path: '/api/v1/admin/businesses/{business}/cancel-subscription',
        tags: ['Admin Businesses'],
        summary: 'Cancel a business subscription locally',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'business', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['reason'],
                properties: [
                    new OA\Property(property: 'reason', type: 'string', maxLength: 500),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Subscription cancelled'),
        ],
    )]
    public function cancelSubscription(Request $request, Business $business): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $business->forceFill([
            'subscription_status' => 'cancelled',
        ])->save();

        $this->logAction($request, 'cancel_subscription', $business, [
            'reason' => $validated['reason'],
        ]);

        return response()->json((new AdminBusinessResource($business->fresh()))->toArray($request));
    }

    #[OA\Post(
        path: '/api/v1/admin/businesses/{business}/impersonate',
        tags: ['Admin Businesses'],
        summary: 'Issue a short-lived impersonation token',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'business', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Impersonation token issued'),
        ],
    )]
    public function impersonate(Request $request, Business $business): JsonResponse
    {
        $expiresAt = now()->addMinutes(15);
        $token = $business->createToken('impersonation', ['impersonate'], $expiresAt)->plainTextToken;

        $this->logAction($request, 'impersonate', $business, [
            'business_id' => $business->id,
            'business_name' => $business->name,
        ]);

        return response()->json([
            'impersonation_token' => $token,
            'business_name' => $business->name,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    private function logAction(Request $request, string $action, Business $business, array $payload): void
    {
        /** @var Admin|null $admin */
        $admin = $request->user();

        AdminAuditLog::query()->create([
            'admin_id' => $admin?->id,
            'action' => $action,
            'target_type' => 'Business',
            'target_id' => $business->id,
            'payload' => $payload,
            'ip_address' => $request->ip(),
        ]);
    }
}

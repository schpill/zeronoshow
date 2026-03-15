<?php

namespace App\Http\Controllers\Api;

use App\Enums\ChannelTypeEnum;
use App\Enums\WaitlistStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWaitlistEntryRequest;
use App\Http\Resources\WaitlistEntryResource;
use App\Jobs\NotifyWaitlistJob;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Waitlist', description: 'Waitlist management endpoints')]
class WaitlistController extends Controller
{
    use AuthorizesRequests;

    #[OA\Get(
        path: '/api/v1/waitlist',
        tags: ['Waitlist'],
        summary: 'List waitlist entries',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Waitlist entries')],
    )]
    public function index(Request $request): JsonResponse
    {
        $business = $request->user();

        $query = WaitlistEntry::query()
            ->where('business_id', $business->id);

        if ($request->has('slot_date')) {
            $query->whereDate('slot_date', $request->slot_date);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $entries = $query->orderBy('slot_date')
            ->orderBy('slot_time')
            ->orderBy('priority_order')
            ->paginate(15);

        return WaitlistEntryResource::collection($entries)->response();
    }

    #[OA\Post(
        path: '/api/v1/waitlist',
        tags: ['Waitlist'],
        summary: 'Create waitlist entry',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 201, description: 'Waitlist entry created')],
    )]
    public function store(StoreWaitlistEntryRequest $request): JsonResponse
    {
        $business = $request->user();

        $maxPriority = WaitlistEntry::query()
            ->where('business_id', $business->id)
            ->where('slot_date', $request->slot_date)
            ->where('slot_time', $request->slot_time)
            ->max('priority_order') ?? 0;

        $entry = WaitlistEntry::create([
            ...$request->validated(),
            'business_id' => $business->id,
            'priority_order' => $maxPriority + 1,
            'status' => WaitlistStatusEnum::Pending,
            'channel' => ChannelTypeEnum::Sms,
        ]);

        return (new WaitlistEntryResource($entry))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Delete(
        path: '/api/v1/waitlist/{entry}',
        tags: ['Waitlist'],
        summary: 'Delete waitlist entry',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'entry', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [new OA\Response(response: 204, description: 'Deleted')],
    )]
    public function destroy(WaitlistEntry $entry): JsonResponse
    {
        $this->authorize('delete', $entry);

        if ($entry->status !== WaitlistStatusEnum::Pending) {
            return response()->json(['message' => 'Seules les entrées en attente peuvent être supprimées.'], 422);
        }

        $entry->delete();

        return response()->json(null, 204);
    }

    #[OA\Post(
        path: '/api/v1/waitlist/reorder',
        tags: ['Waitlist'],
        summary: 'Reorder waitlist entries',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Reordered')],
    )]
    public function reorder(Request $request): JsonResponse
    {
        $business = $request->user();
        $request->validate([
            'ordered_ids' => 'required|array',
            'ordered_ids.*' => 'required|uuid|exists:waitlist_entries,id',
        ]);

        $entryIds = $request->ordered_ids;

        DB::transaction(function () use ($business, $entryIds) {
            foreach ($entryIds as $index => $id) {
                WaitlistEntry::query()
                    ->where('id', $id)
                    ->where('business_id', $business->id)
                    ->update(['priority_order' => $index + 1]);
            }
        });

        return response()->json(['message' => 'Ordre mis à jour avec succès.']);
    }

    #[OA\Post(
        path: '/api/v1/waitlist/{entry}/notify',
        tags: ['Waitlist'],
        summary: 'Notify one waitlist entry',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'entry', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [new OA\Response(response: 202, description: 'Notification queued')],
    )]
    public function notify(WaitlistEntry $entry): JsonResponse
    {
        $this->authorize('notify', $entry);

        NotifyWaitlistJob::dispatch(
            $entry->business_id,
            $entry->slot_date->format('Y-m-d'),
            $entry->slot_time
        );

        return response()->json(['message' => 'Notification en cours d\'envoi.'], 202);
    }
}

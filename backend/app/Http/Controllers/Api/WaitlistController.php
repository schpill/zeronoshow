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

class WaitlistController extends Controller
{
    use AuthorizesRequests;

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

    public function destroy(WaitlistEntry $entry): JsonResponse
    {
        $this->authorize('delete', $entry);

        if ($entry->status !== WaitlistStatusEnum::Pending) {
            return response()->json(['message' => 'Seules les entrées en attente peuvent être supprimées.'], 422);
        }

        $entry->delete();

        return response()->json(null, 204);
    }

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

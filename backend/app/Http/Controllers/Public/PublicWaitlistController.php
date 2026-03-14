<?php

namespace App\Http\Controllers\Public;

use App\Enums\ChannelTypeEnum;
use App\Enums\WaitlistStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicStoreWaitlistRequest;
use App\Http\Resources\WaitlistEntryResource;
use App\Models\Business;
use App\Models\WaitlistEntry;
use Illuminate\Http\JsonResponse;

class PublicWaitlistController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $business = Business::query()
            ->where('waitlist_public_token', $token)
            ->where('waitlist_enabled', true)
            ->firstOrFail();

        return response()->json([
            'business_name' => $business->name,
            'slots_available' => $this->getAvailableSlots($business),
        ]);
    }

    public function store(PublicStoreWaitlistRequest $request, string $token): JsonResponse
    {
        $business = Business::query()
            ->where('waitlist_public_token', $token)
            ->where('waitlist_enabled', true)
            ->firstOrFail();

        $maxPriority = WaitlistEntry::query()
            ->where('business_id', $business->id)
            ->whereDate('slot_date', $request->slot_date)
            ->whereTime('slot_time', $request->slot_time)
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

    private function getAvailableSlots(Business $business): array
    {
        // For MVP, just return current day and next 6 days with some default times
        // or actually query for existing slots that have waitlist entries?
        // Let's just return a range of dates for now.
        $slots = [];
        for ($i = 0; $i < 7; $i++) {
            $date = now()->addDays($i)->format('Y-m-d');
            $slots[] = [
                'date' => $date,
                'times' => ['12:00', '12:30', '13:00', '19:00', '19:30', '20:00', '20:30', '21:00'],
            ];
        }

        return $slots;
    }
}

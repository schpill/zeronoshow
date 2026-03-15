<?php

namespace App\Http\Resources;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminBusinessResource extends BusinessResource
{
    public function toArray(Request $request): array
    {
        /** @var Business $business */
        $business = $this->resource;
        $lastReservationAt = data_get($business, 'last_reservation_at');

        return [
            ...parent::toArray($request),
            'reservations_count' => (int) ($business->reservations_count ?? 0),
            'sms_sent_count' => (int) ($business->sms_sent_count ?? 0),
            'last_reservation_at' => $lastReservationAt ? Carbon::parse($lastReservationAt)->toIso8601String() : null,
            'created_at' => optional($business->created_at)->toIso8601String(),
        ];
    }
}

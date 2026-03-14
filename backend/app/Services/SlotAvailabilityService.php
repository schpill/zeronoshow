<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Reservation;
use App\Models\WidgetSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SlotAvailabilityService
{
    public function getAvailableSlots(Business $business, string $date): array
    {
        $cacheKey = sprintf('slots:%s:%s', $business->id, $date);

        return Cache::remember($cacheKey, 30, function () use ($business, $date): array {
            $setting = WidgetSetting::query()
                ->where('business_id', $business->id)
                ->first();

            if ($setting === null || ! $setting->is_enabled) {
                return [];
            }

            $requestedDate = Carbon::parse($date);

            if (now()->diffInDays($requestedDate, false) > $setting->advance_booking_days) {
                return [];
            }

            $today = now($business->timezone)->startOfDay();
            $isToday = $requestedDate->isSameDay($today);

            if ($isToday) {
                $cutoffMinutes = $setting->same_day_cutoff_minutes;
                $earliestSlot = now($business->timezone)->addMinutes($cutoffMinutes)->startOfHour();

                if ($earliestSlot->minute >= 30) {
                    $earliestSlot->addMinutes(30);
                } else {
                    $earliestSlot->minute(0);
                }
            } else {
                $earliestSlot = $requestedDate->copy()->setTime(9, 0);
            }

            $closingTime = $requestedDate->copy()->setTime(22, 0);

            $slots = [];
            $current = $earliestSlot->copy();

            while ($current->lessThan($closingTime)) {
                $slots[] = $current->format('H:i');
                $current->addMinutes(30);
            }

            // Count existing reservations per slot
            $existingCounts = [];
            $reservations = Reservation::query()
                ->where('business_id', $business->id)
                ->whereDate('scheduled_at', $date)
                ->whereNotIn('status', ['cancelled_by_client', 'cancelled_no_confirmation', 'no_show'])
                ->get();

            foreach ($reservations as $reservation) {
                $slotTime = $reservation->scheduled_at->format('H:i');
                $existingCounts[$slotTime] = ($existingCounts[$slotTime] ?? 0) + 1;
            }

            $maxCapacity = $setting->max_party_size;

            return array_values(array_filter(
                $slots,
                fn (string $slot) => ($existingCounts[$slot] ?? 0) < $maxCapacity
            ));
        });
    }
}

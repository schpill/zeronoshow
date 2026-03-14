<?php

namespace App\Services;

use App\Enums\WaitlistStatusEnum;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\WaitlistEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WaitlistService
{
    public function notifyNext(string $businessId, string $slotDate, string $slotTime): ?WaitlistEntry
    {
        return DB::transaction(function () use ($businessId, $slotDate, $slotTime) {
            $entry = WaitlistEntry::query()
                ->where('business_id', $businessId)
                ->whereDate('slot_date', $slotDate)
                ->whereTime('slot_time', $slotTime)
                ->where('status', WaitlistStatusEnum::Pending)
                ->orderBy('priority_order')
                ->lockForUpdate()
                ->first();

            if (! $entry) {
                return null;
            }

            $business = Business::findOrFail($businessId);
            $windowMinutes = $business->waitlist_notification_window_minutes;

            $entry->update([
                'status' => WaitlistStatusEnum::Notified,
                'notified_at' => now(),
                'expires_at' => now()->addMinutes($windowMinutes),
            ]);

            $entry->generateConfirmationToken();

            return $entry;
        });
    }

    public function confirmSlot(WaitlistEntry $entry): Reservation
    {
        return DB::transaction(function () use ($entry) {
            $customer = Customer::firstOrCreate(
                ['phone' => $entry->client_phone]
            );

            $reservation = Reservation::create([
                'business_id' => $entry->business_id,
                'customer_id' => $customer->id,
                'customer_name' => $entry->client_name,
                'scheduled_at' => Carbon::parse("{$entry->slot_date->format('Y-m-d')} {$entry->slot_time}"),
                'guests' => $entry->party_size,
                'status' => 'confirmed',
                'phone_verified' => true, // Already verified by SMS/WhatsApp interaction
            ]);

            $entry->update([
                'status' => WaitlistStatusEnum::Confirmed,
                'confirmed_at' => now(),
            ]);

            // Mark other pending/notified entries for the same slot as expired?
            // Actually, the slot is filled, so we should expire others.
            WaitlistEntry::query()
                ->where('business_id', $entry->business_id)
                ->whereDate('slot_date', $entry->slot_date)
                ->whereTime('slot_time', $entry->slot_time)
                ->whereIn('status', [WaitlistStatusEnum::Pending, WaitlistStatusEnum::Notified])
                ->where('id', '!=', $entry->id)
                ->update(['status' => WaitlistStatusEnum::Expired]);

            return $reservation;
        });
    }

    public function declineSlot(WaitlistEntry $entry): void
    {
        DB::transaction(function () use ($entry) {
            $entry->update([
                'status' => WaitlistStatusEnum::Declined,
                'confirmation_token' => null,
            ]);

            // Automatically notify next
            $this->notifyNext($entry->business_id, $entry->slot_date->format('Y-m-d'), $entry->slot_time);
        });
    }

    public function expireNotification(WaitlistEntry $entry): void
    {
        DB::transaction(function () use ($entry) {
            if ($entry->status !== WaitlistStatusEnum::Notified) {
                return;
            }

            $entry->update([
                'status' => WaitlistStatusEnum::Expired,
                'confirmation_token' => null,
            ]);

            // Automatically notify next
            $this->notifyNext($entry->business_id, $entry->slot_date->format('Y-m-d'), $entry->slot_time);
        });
    }
}

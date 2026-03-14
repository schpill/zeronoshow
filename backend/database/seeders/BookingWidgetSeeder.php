<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\WidgetSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BookingWidgetSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::query()->first();

        if ($business === null) {
            return;
        }

        WidgetSetting::query()->updateOrCreate(
            ['business_id' => $business->id],
            [
                'logo_url' => null,
                'accent_colour' => '#6366f1',
                'max_party_size' => 20,
                'advance_booking_days' => 60,
                'same_day_cutoff_minutes' => 60,
                'is_enabled' => true,
            ],
        );

        // Ensure public_token exists
        if ($business->public_token === null) {
            $business->update(['public_token' => Str::uuid()]);
        }
    }
}

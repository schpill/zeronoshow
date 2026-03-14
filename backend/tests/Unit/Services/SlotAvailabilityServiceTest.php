<?php

namespace Tests\Unit\Services;

use App\Models\Business;
use App\Models\Reservation;
use App\Models\WidgetSetting;
use App\Services\SlotAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SlotAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private SlotAvailabilityService $service;

    private Business $business;

    private WidgetSetting $setting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SlotAvailabilityService::class);
        $this->business = Business::factory()->create();
        $this->setting = WidgetSetting::create([
            'business_id' => $this->business->id,
            'is_enabled' => true,
            'max_party_size' => 2,
            'advance_booking_days' => 30,
            'same_day_cutoff_minutes' => 60,
        ]);
    }

    public function test_slot_is_available_when_count_is_below_capacity(): void
    {
        $date = now()->addDay()->format('Y-m-d');
        $slots = $this->service->getAvailableSlots($this->business, $date);

        $this->assertNotEmpty($slots);
        $this->assertContains('12:00', $slots);
    }

    public function test_slot_is_unavailable_when_reservation_count_reaches_capacity(): void
    {
        $date = now()->addDay()->format('Y-m-d');
        $scheduledAt = now()->addDay()->setTime(12, 0)->utc();

        // Fill to capacity (max_party_size = 2)
        Reservation::factory()->count(2)->create([
            'business_id' => $this->business->id,
            'scheduled_at' => $scheduledAt,
            'status' => 'confirmed',
        ]);

        Cache::flush();
        $slots = $this->service->getAvailableSlots($this->business, $date);

        $this->assertNotContains('12:00', $slots);
    }

    public function test_slot_remains_available_when_count_is_below_capacity(): void
    {
        $date = now()->addDay()->format('Y-m-d');
        $scheduledAt = now()->addDay()->setTime(12, 0)->utc();

        // One reservation — capacity is 2, slot should still appear
        Reservation::factory()->create([
            'business_id' => $this->business->id,
            'scheduled_at' => $scheduledAt,
            'status' => 'confirmed',
        ]);

        Cache::flush();
        $slots = $this->service->getAvailableSlots($this->business, $date);

        $this->assertContains('12:00', $slots);
    }

    public function test_cancelled_reservations_do_not_count_against_capacity(): void
    {
        $date = now()->addDay()->format('Y-m-d');
        $scheduledAt = now()->addDay()->setTime(12, 0)->utc();

        // Two cancelled reservations — slot should still be available
        Reservation::factory()->count(2)->create([
            'business_id' => $this->business->id,
            'scheduled_at' => $scheduledAt,
            'status' => 'cancelled_by_client',
        ]);

        Cache::flush();
        $slots = $this->service->getAvailableSlots($this->business, $date);

        $this->assertContains('12:00', $slots);
    }

    public function test_returns_empty_when_date_exceeds_advance_booking_window(): void
    {
        $date = now()->addDays(31)->format('Y-m-d');

        $slots = $this->service->getAvailableSlots($this->business, $date);

        $this->assertEmpty($slots);
    }

    public function test_returns_empty_when_widget_is_disabled(): void
    {
        $this->setting->update(['is_enabled' => false]);
        Cache::flush();

        $date = now()->addDay()->format('Y-m-d');
        $slots = $this->service->getAvailableSlots($this->business, $date);

        $this->assertEmpty($slots);
    }

    public function test_same_day_cutoff_excludes_slots_too_close_to_now(): void
    {
        // Freeze time at 10:00 — cutoff is 60 min, so slots before 11:00 should be excluded
        $this->travelTo(now()->setTime(10, 0));

        $today = now($this->business->timezone)->format('Y-m-d');
        Cache::flush();

        $slots = $this->service->getAvailableSlots($this->business, $today);

        $this->assertNotContains('09:00', $slots);
        $this->assertNotContains('09:30', $slots);
        $this->assertNotContains('10:00', $slots);
        $this->assertNotContains('10:30', $slots);
        // 11:00 or later should be present
        $this->assertNotEmpty($slots);

        $this->travelBack();
    }
}

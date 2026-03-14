<?php

namespace Tests\Feature\Widget;

use App\Models\Business;
use App\Models\Reservation;
use App\Models\WidgetSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlotAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::factory()->create();
        WidgetSetting::create([
            'business_id' => $this->business->id,
            'max_party_size' => 1,
            'advance_booking_days' => 60,
            'same_day_cutoff_minutes' => 60,
            'is_enabled' => true,
        ]);
    }

    public function test_it_returns_available_slots_for_valid_date(): void
    {
        $date = now()->addDay()->format('Y-m-d');

        $response = $this->getJson('/api/v1/public/widget/'.$this->business->public_token.'/slots?date='.$date);

        $response->assertOk()
            ->assertJsonCount(26, 'slots');
    }

    public function test_it_excludes_past_time_slots_for_today(): void
    {
        $date = now()->format('Y-m-d');

        $response = $this->getJson('/api/v1/public/widget/'.$this->business->public_token.'/slots?date='.$date);

        $response->assertOk();
        $slots = $response->json('slots');
        $this->assertNotEmpty($slots);
        foreach ($slots as $slot) {
            $this->assertGreaterThan((int) now()->format('H'), (int) substr($slot, 0, 2));
        }
    }

    public function test_it_excludes_dates_beyond_advance_booking_days(): void
    {
        $date = now()->addDays(61)->format('Y-m-d');

        $response = $this->getJson('/api/v1/public/widget/'.$this->business->public_token.'/slots?date='.$date);

        $response->assertOk()
            ->assertJsonCount(0, 'slots');
    }

    public function test_it_returns_empty_array_when_fully_booked(): void
    {
        $date = now()->addDay();
        $allSlots = [];
        $current = $date->copy()->setTime(9, 0);
        while ($current->lessThan($date->copy()->setTime(22, 0))) {
            $allSlots[] = $current->copy();
            $current->addMinutes(30);
        }

        foreach ($allSlots as $slot) {
            Reservation::factory()->create([
                'business_id' => $this->business->id,
                'customer_name' => 'Test Customer '.$slot->format('Hi'),
                'scheduled_at' => $slot,
                'guests' => 1,
                'status' => 'confirmed',
                'phone_verified' => true,
                'status_changed_at' => now(),
            ]);
        }

        $response = $this->getJson('/api/v1/public/widget/'.$this->business->public_token.'/slots?date='.$date->format('Y-m-d'));

        $response->assertOk()
            ->assertJsonCount(0, 'slots');
    }

    public function test_it_returns_422_when_date_missing(): void
    {
        $response = $this->getJson('/api/v1/public/widget/'.$this->business->public_token.'/slots');

        $response->assertStatus(422);
    }
}

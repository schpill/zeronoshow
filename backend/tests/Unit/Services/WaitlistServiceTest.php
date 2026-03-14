<?php

namespace Tests\Unit\Services;

use App\Enums\WaitlistStatusEnum;
use App\Models\Business;
use App\Models\Customer;
use App\Models\WaitlistEntry;
use App\Services\WaitlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaitlistServiceTest extends TestCase
{
    use RefreshDatabase;

    private WaitlistService $service;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WaitlistService();
        $this->business = Business::factory()->create([
            'waitlist_enabled' => true,
            'waitlist_notification_window_minutes' => 15,
        ]);
    }

    public function test_notify_next_returns_null_when_queue_is_empty(): void
    {
        $result = $this->service->notifyNext($this->business->id, '2026-03-30', '19:30:00');
        $this->assertNull($result);
    }

    public function test_notify_next_returns_lowest_priority_entry_and_sets_status_notified(): void
    {
        $date = now()->addDays(1)->format('Y-m-d');
        WaitlistEntry::factory()->create([
            'business_id' => $this->business->id,
            'slot_date' => $date,
            'slot_time' => '19:30:00',
            'priority_order' => 2,
            'status' => WaitlistStatusEnum::Pending,
        ]);

        $first = WaitlistEntry::factory()->create([
            'business_id' => $this->business->id,
            'slot_date' => $date,
            'slot_time' => '19:30:00',
            'priority_order' => 1,
            'status' => WaitlistStatusEnum::Pending,
        ]);

        $result = $this->service->notifyNext($this->business->id, $date, '19:30:00');

        if ($result === null) {
            $all = WaitlistEntry::all();
            fwrite(STDERR, "Entries in DB: " . json_encode($all->toArray()) . "\n");
            fwrite(STDERR, "Searching for: business_id={$this->business->id}, slot_date={$date}, slot_time=19:30:00\n");
        }

        $this->assertNotNull($result);
        $this->assertEquals($first->id, $result->id);
        $this->assertEquals(WaitlistStatusEnum::Notified, $result->status);
        $this->assertNotNull($result->notified_at);
        $this->assertNotNull($result->expires_at);
        $this->assertNotNull($result->confirmation_token);
    }

    public function test_confirm_slot_creates_reservation_and_marks_entry_confirmed(): void
    {
        $date = now()->addDays(1)->format('Y-m-d');
        $entry = WaitlistEntry::factory()->create([
            'business_id' => $this->business->id,
            'slot_date' => $date,
            'slot_time' => '19:30:00',
            'status' => WaitlistStatusEnum::Notified,
        ]);

        $reservation = $this->service->confirmSlot($entry);

        $this->assertEquals($this->business->id, $reservation->business_id);
        $this->assertEquals($entry->client_name, $reservation->customer_name);
        $this->assertEquals($date . ' 19:30:00', $reservation->scheduled_at->format('Y-m-d H:i:s'));
        
        $this->assertEquals(WaitlistStatusEnum::Confirmed, $entry->fresh()->status);
        $this->assertNotNull($entry->fresh()->confirmed_at);
    }

    public function test_confirm_slot_expires_other_entries_for_same_slot(): void
    {
        $date = now()->addDays(1)->format('Y-m-d');
        $entry = WaitlistEntry::factory()->create([
            'business_id' => $this->business->id,
            'slot_date' => $date,
            'slot_time' => '19:30:00',
            'status' => WaitlistStatusEnum::Notified,
        ]);

        $other = WaitlistEntry::factory()->create([
            'business_id' => $this->business->id,
            'slot_date' => $date,
            'slot_time' => '19:30:00',
            'status' => WaitlistStatusEnum::Pending,
        ]);

        $this->service->confirmSlot($entry);

        $this->assertEquals(WaitlistStatusEnum::Expired, $other->fresh()->status);
    }
}

<?php

namespace Tests\Unit\Jobs;

use App\Jobs\NotifyWaitlistJob;
use App\Models\Business;
use App\Models\SmsLog;
use App\Models\WaitlistEntry;
use App\Services\Contracts\SmsServiceInterface;
use App\Services\WaitlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class NotifyWaitlistJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_notifies_next_entry_and_sends_sms(): void
    {
        $business = Business::factory()->create([
            'waitlist_enabled' => true,
            'waitlist_notification_window_minutes' => 15,
        ]);

        $entry = WaitlistEntry::factory()->create([
            'business_id' => $business->id,
            'slot_date' => now()->addDay()->format('Y-m-d'),
            'slot_time' => '19:30:00',
            'status' => 'pending',
            'confirmation_token' => 'test-token',
        ]);

        $waitlistService = $this->createMock(WaitlistService::class);
        $waitlistService->expects($this->once())
            ->method('notifyNext')
            ->with($business->id, $entry->slot_date->format('Y-m-d'), '19:30:00')
            ->willReturn($entry);

        $smsService = $this->createMock(SmsServiceInterface::class);
        $smsService->expects($this->once())
            ->method('send')
            ->with($this->callback(function (SmsLog $log) use ($entry, $business) {
                return $log->phone === $entry->client_phone &&
                       str_contains($log->body, $business->name);
            }));

        $job = new NotifyWaitlistJob($business->id, $entry->slot_date->format('Y-m-d'), '19:30:00');
        $job->handle($waitlistService, $smsService);
    }

    public function test_handle_logs_when_no_entries_left(): void
    {
        $business = Business::factory()->create();

        $waitlistService = $this->createMock(WaitlistService::class);
        $waitlistService->method('notifyNext')->willReturn(null);

        $smsService = $this->createMock(SmsServiceInterface::class);
        $smsService->expects($this->never())->method('send');

        Log::shouldReceive('info')->with(Mockery::pattern('/No pending waitlist entries/'));

        $job = new NotifyWaitlistJob($business->id, now()->format('Y-m-d'), '19:30:00');
        $job->handle($waitlistService, $smsService);
    }
}

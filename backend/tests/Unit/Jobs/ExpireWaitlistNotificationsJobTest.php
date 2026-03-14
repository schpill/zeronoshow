<?php

namespace Tests\Unit\Jobs;

use App\Enums\WaitlistStatusEnum;
use App\Jobs\ExpireWaitlistNotificationsJob;
use App\Models\WaitlistEntry;
use App\Services\WaitlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireWaitlistNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_expires_notified_entries_past_expiration(): void
    {
        $entry = WaitlistEntry::factory()->create([
            'status' => WaitlistStatusEnum::Notified,
            'expires_at' => now()->subMinute(),
        ]);

        $other = WaitlistEntry::factory()->create([
            'status' => WaitlistStatusEnum::Notified,
            'expires_at' => now()->addMinutes(10),
        ]);

        $waitlistService = $this->createMock(WaitlistService::class);
        $waitlistService->expects($this->once())
            ->method('expireNotification')
            ->with($this->callback(fn ($e) => $e->id === $entry->id));

        $job = new ExpireWaitlistNotificationsJob;
        $job->handle($waitlistService);
    }
}

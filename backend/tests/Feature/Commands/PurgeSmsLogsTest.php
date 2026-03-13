<?php

namespace Tests\Feature\Commands;

use App\Models\SmsLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeSmsLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_old_sms_logs_without_deleting_them_in_dry_run_mode(): void
    {
        $oldLog = SmsLog::factory()->create([
            'created_at' => now()->subDays(91),
            'queued_at' => now()->subDays(91),
        ]);

        $this->artisan('sms-logs:purge --dry-run')
            ->expectsOutputToContain('1 sms logs older than 90 days would be deleted')
            ->assertExitCode(0);

        $this->assertDatabaseHas('sms_logs', ['id' => $oldLog->id]);
    }

    public function test_it_deletes_sms_logs_older_than_ninety_days(): void
    {
        $oldLog = SmsLog::factory()->create([
            'created_at' => now()->subDays(91),
            'queued_at' => now()->subDays(91),
        ]);
        $recentLog = SmsLog::factory()->create([
            'created_at' => now()->subDays(10),
            'queued_at' => now()->subDays(10),
        ]);

        $this->artisan('sms-logs:purge')
            ->expectsOutputToContain('Deleted 1 sms logs older than 90 days')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('sms_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('sms_logs', ['id' => $recentLog->id]);
    }
}

<?php

namespace Tests\Unit\Leo;

use App\Models\LeoMessageLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeLeoMessageLogsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_records_older_than_the_default_retention_window(): void
    {
        LeoMessageLog::factory()->create(['channel_id' => null, 'created_at' => now()->subDays(91)]);
        LeoMessageLog::factory()->create(['channel_id' => null, 'created_at' => now()->subDays(10)]);

        $this->artisan('leo-logs:purge')
            ->expectsOutput('Deleted 1 Leo message logs older than 90 days')
            ->assertSuccessful();

        $this->assertSame(1, LeoMessageLog::query()->count());
    }

    public function test_it_respects_the_custom_days_option(): void
    {
        LeoMessageLog::factory()->create(['channel_id' => null, 'created_at' => now()->subDays(8)]);
        LeoMessageLog::factory()->create(['channel_id' => null, 'created_at' => now()->subDays(4)]);

        $this->artisan('leo-logs:purge', ['--days' => 7])
            ->expectsOutput('Deleted 1 Leo message logs older than 7 days')
            ->assertSuccessful();

        $this->assertSame(1, LeoMessageLog::query()->count());
    }
}

<?php

namespace Tests\Feature\Leo;

use App\Models\LeoMessageLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeoMessageLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_insert_only(): void
    {
        $log = LeoMessageLog::factory()->create([
            'raw_message' => 'Premier message',
        ]);

        $updated = $log->update([
            'raw_message' => 'Message modifié',
        ]);

        $this->assertFalse($updated);
        $this->assertDatabaseHas('leo_message_logs', [
            'id' => $log->id,
            'raw_message' => 'Premier message',
        ]);
    }
}

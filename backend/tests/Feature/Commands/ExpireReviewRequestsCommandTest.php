<?php

namespace Tests\Feature\Commands;

use App\Models\ReviewRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireReviewRequestsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_sent_requests_with_past_expiry_as_expired(): void
    {
        $expired = ReviewRequest::factory()->create([
            'status' => 'sent',
            'expires_at' => now()->subDay(),
        ]);
        ReviewRequest::factory()->create([
            'status' => 'clicked',
            'expires_at' => now()->subDay(),
        ]);
        ReviewRequest::factory()->create([
            'status' => 'sent',
            'expires_at' => now()->addDay(),
        ]);

        $this->artisan('review-requests:expire')
            ->expectsOutputToContain('Expired 1 review requests')
            ->assertExitCode(0);

        $this->assertDatabaseHas('review_requests', [
            'id' => $expired->id,
            'status' => 'expired',
        ]);
    }
}

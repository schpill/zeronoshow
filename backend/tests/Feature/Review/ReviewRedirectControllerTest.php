<?php

namespace Tests\Feature\Review;

use App\Models\ReviewRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewRedirectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_tracks_click_and_redirects_to_review_url(): void
    {
        $reviewRequest = ReviewRequest::factory()->create([
            'status' => 'sent',
            'review_url' => 'https://example.test/review',
            'short_code' => 'abc12345',
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->get('/r/'.$reviewRequest->short_code);

        $response->assertRedirect('https://example.test/review');
        $this->assertDatabaseHas('review_requests', [
            'id' => $reviewRequest->id,
            'status' => 'clicked',
        ]);
    }

    public function test_redirect_returns_not_found_for_expired_request(): void
    {
        $reviewRequest = ReviewRequest::factory()->create([
            'status' => 'sent',
            'short_code' => 'expired1',
            'expires_at' => now()->subMinute(),
        ]);

        $this->get('/r/'.$reviewRequest->short_code)->assertNotFound();
    }
}

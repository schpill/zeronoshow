<?php

namespace Tests\Unit\Services;

use App\Jobs\SendReviewRequestJob;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ReviewRequest;
use App\Services\ReviewRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReviewRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_and_send_returns_null_when_review_requests_are_disabled(): void
    {
        Queue::fake();

        $reservation = Reservation::factory()->create([
            'business_id' => Business::factory()->create([
                'review_requests_enabled' => false,
            ])->id,
        ]);

        $service = app(ReviewRequestService::class);

        $this->assertNull($service->createAndSend($reservation));
        Queue::assertNothingPushed();
    }

    public function test_create_and_send_creates_review_request_and_dispatches_job_with_delay(): void
    {
        Queue::fake();

        $business = Business::factory()->create([
            'review_requests_enabled' => true,
            'review_platform' => 'google',
            'review_delay_hours' => 4,
            'google_place_id' => 'ChIJ123456789',
        ]);
        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'customer_id' => Customer::factory()->create()->id,
        ]);

        $service = app(ReviewRequestService::class);

        $reviewRequest = $service->createAndSend($reservation);

        $this->assertNotNull($reviewRequest);
        $this->assertDatabaseHas('review_requests', [
            'reservation_id' => $reservation->id,
            'business_id' => $business->id,
            'platform' => 'google',
            'status' => 'pending',
        ]);

        Queue::assertPushed(SendReviewRequestJob::class, function (SendReviewRequestJob $job) use ($reviewRequest): bool {
            return $job->reviewRequestId === $reviewRequest?->id;
        });
    }

    public function test_create_and_send_returns_existing_null_when_request_already_exists(): void
    {
        Queue::fake();

        $business = Business::factory()->create([
            'review_requests_enabled' => true,
            'review_platform' => 'google',
            'review_delay_hours' => 2,
            'google_place_id' => 'ChIJ123456789',
        ]);
        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'customer_id' => Customer::factory()->create()->id,
        ]);
        ReviewRequest::factory()->create([
            'reservation_id' => $reservation->id,
            'business_id' => $business->id,
            'customer_id' => $reservation->customer_id,
        ]);

        $service = app(ReviewRequestService::class);

        $this->assertNull($service->createAndSend($reservation));
        Queue::assertNothingPushed();
    }
}

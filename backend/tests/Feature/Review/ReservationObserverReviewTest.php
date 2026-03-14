<?php

namespace Tests\Feature\Review;

use App\Jobs\SendReviewRequestJob;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReservationObserverReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_review_request_job_when_status_changes_to_show_and_reviews_are_enabled(): void
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
            'status' => 'confirmed',
        ]);

        $reservation->update(['status' => 'show']);

        Queue::assertPushed(SendReviewRequestJob::class);
    }

    public function test_it_does_not_dispatch_review_request_job_when_reviews_are_disabled(): void
    {
        Queue::fake();

        $business = Business::factory()->create([
            'review_requests_enabled' => false,
            'review_platform' => 'google',
            'google_place_id' => 'ChIJ123456789',
        ]);
        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'customer_id' => Customer::factory()->create()->id,
            'status' => 'confirmed',
        ]);

        $reservation->update(['status' => 'show']);

        Queue::assertNotPushed(SendReviewRequestJob::class);
    }
}

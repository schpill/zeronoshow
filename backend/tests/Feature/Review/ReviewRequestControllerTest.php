<?php

namespace Tests\Feature\Review;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ReviewRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_review_requests_for_authenticated_business(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $reviewRequest = ReviewRequest::factory()->create(['business_id' => $business->id]);
        ReviewRequest::factory()->create(['business_id' => $otherBusiness->id]);

        Sanctum::actingAs($business);

        $this->getJson('/api/v1/review-requests')
            ->assertOk()
            ->assertJsonPath('data.0.id', $reviewRequest->id)
            ->assertJsonCount(1, 'data');
    }

    public function test_index_filters_by_status(): void
    {
        $business = Business::factory()->create();
        ReviewRequest::factory()->create(['business_id' => $business->id, 'status' => 'clicked']);
        $sent = ReviewRequest::factory()->create(['business_id' => $business->id, 'status' => 'sent']);

        Sanctum::actingAs($business);

        $this->getJson('/api/v1/review-requests?status=sent')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $sent->id);
    }

    public function test_stats_returns_total_sent_and_click_rate_percent(): void
    {
        $business = Business::factory()->create();
        $customer = Customer::factory()->create();
        $reservation = Reservation::factory()->create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
        ]);

        ReviewRequest::factory()->create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'reservation_id' => $reservation->id,
            'status' => 'clicked',
            'sent_at' => now()->subDay(),
            'clicked_at' => now()->subHours(12),
        ]);

        ReviewRequest::factory()->create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'reservation_id' => Reservation::factory()->create([
                'business_id' => $business->id,
                'customer_id' => $customer->id,
            ])->id,
            'status' => 'sent',
            'sent_at' => now()->subHours(6),
        ]);

        Sanctum::actingAs($business);

        $this->getJson('/api/v1/review-requests/stats')
            ->assertOk()
            ->assertJsonPath('total_sent', 2)
            ->assertJsonPath('total_clicked', 1)
            ->assertJsonPath('click_rate_percent', 50);
    }
}

<?php

namespace Tests\Feature\Review;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_current_settings(): void
    {
        $business = Business::factory()->create([
            'review_requests_enabled' => true,
            'review_platform' => 'google',
            'review_delay_hours' => 4,
            'google_place_id' => 'ChIJ123456789',
        ]);

        Sanctum::actingAs($business);

        $this->getJson('/api/v1/review-settings')
            ->assertOk()
            ->assertJsonPath('data.review_requests_enabled', true)
            ->assertJsonPath('data.review_platform', 'google')
            ->assertJsonPath('data.review_delay_hours', 4)
            ->assertJsonPath('data.google_place_id', 'ChIJ123456789');
    }

    public function test_update_enables_review_requests_with_google_platform(): void
    {
        $business = Business::factory()->create();

        Sanctum::actingAs($business);

        $this->patchJson('/api/v1/review-settings', [
            'review_requests_enabled' => true,
            'review_platform' => 'google',
            'review_delay_hours' => 8,
            'google_place_id' => 'ChIJ123456789',
            'tripadvisor_location_id' => null,
        ])->assertOk()
            ->assertJsonPath('data.review_requests_enabled', true)
            ->assertJsonPath('data.review_platform', 'google')
            ->assertJsonPath('data.review_delay_hours', 8)
            ->assertJsonPath('data.google_place_id', 'ChIJ123456789');
    }

    public function test_update_requires_google_place_id_when_google_is_enabled(): void
    {
        $business = Business::factory()->create();

        Sanctum::actingAs($business);

        $this->patchJson('/api/v1/review-settings', [
            'review_requests_enabled' => true,
            'review_platform' => 'google',
            'review_delay_hours' => 2,
            'google_place_id' => null,
        ])->assertStatus(422)->assertJsonValidationErrors(['google_place_id']);
    }

    public function test_update_saves_tripadvisor_location_id(): void
    {
        $business = Business::factory()->create();

        Sanctum::actingAs($business);

        $this->patchJson('/api/v1/review-settings', [
            'review_requests_enabled' => true,
            'review_platform' => 'tripadvisor',
            'review_delay_hours' => 24,
            'google_place_id' => null,
            'tripadvisor_location_id' => 'd12345',
        ])->assertOk()
            ->assertJsonPath('data.review_platform', 'tripadvisor')
            ->assertJsonPath('data.tripadvisor_location_id', 'd12345');
    }
}

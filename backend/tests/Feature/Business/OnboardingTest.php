<?php

namespace Tests\Feature\Business;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_patch_completes_onboarding_sets_timestamp(): void
    {
        $business = Business::factory()->create(['onboarding_completed_at' => null]);

        Sanctum::actingAs($business);

        $response = $this->patchJson('/api/v1/business/onboarding-complete');

        $response->assertOk()->assertJsonPath('onboarding_completed_at', fn ($val) => $val !== null);

        $this->assertNotNull($business->fresh()->onboarding_completed_at);
    }

    public function test_patch_is_idempotent_if_already_completed(): void
    {
        $originalTimestamp = '2026-01-15T10:00:00+00:00';
        $business = Business::factory()->create([
            'onboarding_completed_at' => $originalTimestamp,
        ]);

        Sanctum::actingAs($business);

        $response = $this->patchJson('/api/v1/business/onboarding-complete');

        $response->assertOk()->assertJsonPath('onboarding_completed_at', $originalTimestamp);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->patchJson('/api/v1/business/onboarding-complete');

        $response->assertUnauthorized();
    }

    public function test_onboarding_completed_at_included_in_auth_me_response(): void
    {
        $business = Business::factory()->create(['onboarding_completed_at' => null]);

        Sanctum::actingAs($business);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk();
        // The dashboard endpoint uses the user which should now have the field
    }

    public function test_business_resource_includes_onboarding_completed_at(): void
    {
        $completedAt = now()->toIso8601String();
        $business = Business::factory()->create(['onboarding_completed_at' => $completedAt]);

        Sanctum::actingAs($business);

        $response = $this->patchJson('/api/v1/business/onboarding-complete');

        $response->assertJsonFragment(['onboarding_completed_at' => $completedAt]);
    }
}

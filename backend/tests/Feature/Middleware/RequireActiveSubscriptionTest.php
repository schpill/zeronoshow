<?php

namespace Tests\Feature\Middleware;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequireActiveSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_blocks_an_expired_trial(): void
    {
        $business = Business::factory()->create([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->subDay(),
        ]);

        Sanctum::actingAs($business);

        $response = $this->getJson('/api/v1/customers/lookup?phone=+33612345678');

        $response
            ->assertStatus(402)
            ->assertJsonPath('error.code', 'SUBSCRIPTION_REQUIRED');
    }
}

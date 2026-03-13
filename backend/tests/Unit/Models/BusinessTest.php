<?php

namespace Tests\Unit\Models;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_detects_an_active_subscription(): void
    {
        $business = new Business([
            'subscription_status' => 'active',
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->assertTrue($business->isOnActivePlan());
    }

    public function test_it_detects_an_active_trial(): void
    {
        $business = new Business([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDay(),
        ]);

        $this->assertTrue($business->isOnActivePlan());
    }

    public function test_it_rejects_an_expired_trial(): void
    {
        $business = new Business([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->assertFalse($business->isOnActivePlan());
    }

    public function test_it_rejects_a_cancelled_subscription(): void
    {
        $business = new Business([
            'subscription_status' => 'cancelled',
            'trial_ends_at' => now()->addDay(),
        ]);

        $this->assertFalse($business->isOnActivePlan());
    }

    public function test_it_applies_a_database_default_trial_end_date(): void
    {
        $business = Business::query()->create([
            'name' => 'Le Bistrot',
            'email' => 'owner@example.com',
            'password' => bcrypt('password123'),
            'phone' => '+33612345678',
            'timezone' => 'Europe/Paris',
            'subscription_status' => 'trial',
        ]);

        $this->assertNotNull($business->fresh()->trial_ends_at);
        $this->assertTrue($business->fresh()->trial_ends_at->isFuture());
    }
}

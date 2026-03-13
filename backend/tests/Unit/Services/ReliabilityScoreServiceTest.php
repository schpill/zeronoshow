<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Services\ReliabilityScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReliabilityScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_at_risk_for_a_null_score(): void
    {
        $this->assertSame('at_risk', ReliabilityScoreService::getTierForScore(null));
    }

    public function test_it_returns_reliable_for_scores_at_or_above_ninety(): void
    {
        $this->assertSame('reliable', ReliabilityScoreService::getTierForScore(90.0));
        $this->assertSame('reliable', ReliabilityScoreService::getTierForScore(100.0));
    }

    public function test_it_returns_average_for_scores_between_seventy_and_eighty_nine(): void
    {
        $this->assertSame('average', ReliabilityScoreService::getTierForScore(70.0));
        $this->assertSame('average', ReliabilityScoreService::getTierForScore(89.99));
    }

    public function test_it_returns_at_risk_for_scores_below_seventy(): void
    {
        $this->assertSame('at_risk', ReliabilityScoreService::getTierForScore(69.99));
    }

    public function test_it_recalculates_a_null_score_when_customer_has_no_history(): void
    {
        $customer = Customer::factory()->create([
            'shows_count' => 0,
            'no_shows_count' => 0,
            'reliability_score' => 88.0,
        ]);

        $service = app(ReliabilityScoreService::class);
        $updated = $service->recalculate($customer);

        $this->assertNull($updated->reliability_score);
        $this->assertSame('at_risk', $updated->score_tier);
        $this->assertNotNull($updated->last_calculated_at);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'reliability_score' => null,
            'score_tier' => 'at_risk',
        ]);
    }

    public function test_it_recalculates_customer_score_and_tier(): void
    {
        $customer = Customer::factory()->create([
            'shows_count' => 4,
            'no_shows_count' => 1,
        ]);

        $service = app(ReliabilityScoreService::class);
        $updated = $service->recalculate($customer);

        $this->assertSame(80.0, $updated->reliability_score);
        $this->assertSame('average', $updated->score_tier);
        $this->assertNotNull($updated->last_calculated_at);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'reliability_score' => 80.0,
            'score_tier' => 'average',
        ]);
    }

    public function test_it_rounds_scores_to_two_decimals(): void
    {
        $customer = Customer::factory()->create([
            'shows_count' => 2,
            'no_shows_count' => 1,
        ]);

        $updated = app(ReliabilityScoreService::class)->recalculate($customer);

        $this->assertSame(66.67, $updated->reliability_score);
        $this->assertSame('at_risk', $updated->score_tier);
    }
}

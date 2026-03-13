<?php

namespace Tests\Unit\Jobs;

use App\Jobs\RecalculateReliabilityScore;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use App\Services\ReliabilityScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RecalculateReliabilityScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_recalculates_the_customer_score(): void
    {
        $business = Business::factory()->create();
        $customer = Customer::factory()->create();
        Reservation::factory()->create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
        ]);
        Cache::put("dashboard:{$business->id}:today:list", ['cached' => true], 300);

        $service = Mockery::mock(ReliabilityScoreService::class);
        $service->shouldReceive('recalculate')
            ->once()
            ->withArgs(fn (Customer $candidate): bool => $candidate->is($customer))
            ->andReturnUsing(function (Customer $candidate): Customer {
                $candidate->forceFill([
                    'reliability_score' => 100.0,
                    'score_tier' => 'reliable',
                    'last_calculated_at' => now(),
                ])->save();

                return $candidate->fresh();
            });

        (new RecalculateReliabilityScore($customer->id))->handle($service);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'reliability_score' => 100.0,
            'score_tier' => 'reliable',
        ]);
        $this->assertNull(Cache::get("dashboard:{$business->id}:today:list"));
    }

    public function test_it_ignores_missing_customers(): void
    {
        $service = Mockery::mock(ReliabilityScoreService::class);
        $service->shouldNotReceive('recalculate');

        (new RecalculateReliabilityScore('missing-customer-id'))->handle($service);

        $this->assertTrue(true);
    }

    public function test_it_has_expected_retry_configuration(): void
    {
        $job = new RecalculateReliabilityScore('customer-id');

        $this->assertSame(3, $job->tries);
        $this->assertSame([30, 60, 120], $job->backoff);
    }

    public function test_it_bubbles_service_failures_for_retry(): void
    {
        $customer = Customer::factory()->create();

        $service = Mockery::mock(ReliabilityScoreService::class);
        $service->shouldReceive('recalculate')
            ->once()
            ->andThrow(new RuntimeException('boom'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('boom');

        (new RecalculateReliabilityScore($customer->id))->handle($service);
    }
}

<?php

namespace Tests\Feature\Customer;

use App\Models\Business;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LookupCustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_customer_reliability_information(): void
    {
        $business = Business::factory()->create();
        $customer = Customer::factory()->create([
            'phone' => '+33612345678',
            'reliability_score' => 94,
            'opted_out' => true,
        ]);

        Sanctum::actingAs($business);

        $response = $this->getJson('/api/v1/customers/lookup?phone='.urlencode($customer->phone));

        $response
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('score_tier', 'reliable')
            ->assertJsonPath('opted_out', true);
    }
}

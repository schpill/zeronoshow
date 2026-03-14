<?php

namespace Tests\Feature\Crm;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_filters_vip_customers(): void
    {
        $business = Business::factory()->create();
        $vip = Customer::factory()->create(['is_vip' => true]);
        $standard = Customer::factory()->create(['is_vip' => false]);

        Reservation::factory()->create(['business_id' => $business->id, 'customer_id' => $vip->id]);
        Reservation::factory()->create(['business_id' => $business->id, 'customer_id' => $standard->id]);

        Sanctum::actingAs($business);

        $this->getJson('/api/v1/customers?filter[is_vip]=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $vip->id);
    }

    public function test_index_filters_blacklisted_customers(): void
    {
        $business = Business::factory()->create();
        $blacklisted = Customer::factory()->create(['is_blacklisted' => true]);
        $standard = Customer::factory()->create(['is_blacklisted' => false]);

        Reservation::factory()->create(['business_id' => $business->id, 'customer_id' => $blacklisted->id]);
        Reservation::factory()->create(['business_id' => $business->id, 'customer_id' => $standard->id]);

        Sanctum::actingAs($business);

        $this->getJson('/api/v1/customers?filter[is_blacklisted]=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $blacklisted->id);
    }

    public function test_index_filters_customers_by_birthday_month(): void
    {
        $business = Business::factory()->create();
        $march = Customer::factory()->create(['birthday_month' => 3, 'birthday_day' => 14]);
        $april = Customer::factory()->create(['birthday_month' => 4, 'birthday_day' => 2]);

        Reservation::factory()->create(['business_id' => $business->id, 'customer_id' => $march->id]);
        Reservation::factory()->create(['business_id' => $business->id, 'customer_id' => $april->id]);

        Sanctum::actingAs($business);

        $this->getJson('/api/v1/customers?filter[birthday_month]=3')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $march->id);
    }
}

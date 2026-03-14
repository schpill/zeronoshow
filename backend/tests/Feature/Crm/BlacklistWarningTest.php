<?php

namespace Tests\Feature\Crm;

use App\Models\Business;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BlacklistWarningTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_reservation_response_includes_customer_blacklisted_true_for_blacklisted_phone(): void
    {
        Queue::fake();
        $business = Business::factory()->create();
        $customer = Customer::factory()->create([
            'phone' => '+33612345678',
            'is_blacklisted' => true,
        ]);

        Sanctum::actingAs($business);

        $this->postJson('/api/v1/reservations', [
            'customer_name' => 'Marc Dubois',
            'phone' => $customer->phone,
            'scheduled_at' => Carbon::now()->addDay()->toIso8601String(),
            'guests' => 2,
            'phone_verified' => false,
        ])->assertCreated()
            ->assertJsonPath('reservation.customer_blacklisted', true)
            ->assertJsonPath('customer.is_blacklisted', true);
    }

    public function test_lookup_includes_blacklist_flag_for_existing_customer(): void
    {
        $business = Business::factory()->create();
        $customer = Customer::factory()->create([
            'phone' => '+33612345678',
            'is_blacklisted' => true,
        ]);

        Sanctum::actingAs($business);

        $this->getJson('/api/v1/customers/lookup?phone='.urlencode($customer->phone))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('is_blacklisted', true);
    }

    public function test_store_reservation_response_defaults_to_not_blacklisted_for_new_customer(): void
    {
        Queue::fake();
        $business = Business::factory()->create();

        Sanctum::actingAs($business);

        $this->postJson('/api/v1/reservations', [
            'customer_name' => 'Nouveau client',
            'phone' => '+33699990000',
            'scheduled_at' => Carbon::now()->addDay()->toIso8601String(),
            'guests' => 2,
            'phone_verified' => false,
        ])->assertCreated()
            ->assertJsonPath('reservation.customer_blacklisted', false)
            ->assertJsonPath('customer.is_blacklisted', false);
    }
}

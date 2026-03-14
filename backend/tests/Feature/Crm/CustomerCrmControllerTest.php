<?php

namespace Tests\Feature\Crm;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerCrmControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_sets_customer_crm_fields(): void
    {
        $business = Business::factory()->create();
        $customer = Customer::factory()->create();
        Reservation::factory()->create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
        ]);

        Sanctum::actingAs($business);

        $response = $this->patchJson("/api/v1/customers/{$customer->id}/crm", [
            'notes' => 'Client fidèle, préfère le calme.',
            'is_vip' => true,
            'is_blacklisted' => true,
            'birthday_month' => 3,
            'birthday_day' => 14,
            'preferred_table_notes' => 'Table près de la fenêtre',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.notes', 'Client fidèle, préfère le calme.')
            ->assertJsonPath('data.is_vip', true)
            ->assertJsonPath('data.is_blacklisted', true)
            ->assertJsonPath('data.birthday_month', 3)
            ->assertJsonPath('data.birthday_day', 14)
            ->assertJsonPath('data.preferred_table_notes', 'Table près de la fenêtre');
    }

    public function test_update_clears_birthday_fields_when_null(): void
    {
        $business = Business::factory()->create();
        $customer = Customer::factory()->create([
            'birthday_month' => 3,
            'birthday_day' => 14,
        ]);
        Reservation::factory()->create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
        ]);

        Sanctum::actingAs($business);

        $response = $this->patchJson("/api/v1/customers/{$customer->id}/crm", [
            'birthday_month' => null,
            'birthday_day' => null,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.birthday_month', null)
            ->assertJsonPath('data.birthday_day', null);
    }

    public function test_update_returns_forbidden_for_customer_from_another_business(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $customer = Customer::factory()->create();

        Reservation::factory()->create([
            'business_id' => $otherBusiness->id,
            'customer_id' => $customer->id,
        ]);

        Sanctum::actingAs($business);

        $this->patchJson("/api/v1/customers/{$customer->id}/crm", [
            'is_vip' => true,
        ])->assertForbidden();
    }

    public function test_update_returns_validation_error_for_invalid_birthday_month(): void
    {
        $business = Business::factory()->create();
        $customer = Customer::factory()->create();
        Reservation::factory()->create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
        ]);

        Sanctum::actingAs($business);

        $this->patchJson("/api/v1/customers/{$customer->id}/crm", [
            'birthday_month' => 13,
        ])->assertStatus(422)->assertJsonValidationErrors(['birthday_month']);
    }
}

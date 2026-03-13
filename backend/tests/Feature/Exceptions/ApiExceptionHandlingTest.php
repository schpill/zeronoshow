<?php

namespace Tests\Feature\Exceptions;

use App\Models\Business;
use App\Models\Reservation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiExceptionHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_json_not_found_payload_for_missing_models(): void
    {
        $business = Business::factory()->create();
        Sanctum::actingAs($business);

        $this->getJson('/api/v1/reservations/00000000-0000-0000-0000-000000000000')
            ->assertNotFound()
            ->assertJson([
                'error' => [
                    'code' => 'NOT_FOUND',
                ],
            ]);
    }

    public function test_it_returns_a_json_forbidden_payload_for_authorization_failures(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $reservation = Reservation::factory()->create([
            'business_id' => $otherBusiness->id,
        ]);

        Sanctum::actingAs($business);

        $this->getJson("/api/v1/reservations/{$reservation->id}")
            ->assertForbidden()
            ->assertJson([
                'error' => [
                    'code' => 'FORBIDDEN',
                ],
            ]);
    }

    public function test_it_returns_a_generic_json_payload_for_unhandled_errors(): void
    {
        Route::get('/api/v1/test-runtime-exception', function (): void {
            throw new \RuntimeException('boom');
        });

        $this->getJson('/api/v1/test-runtime-exception')
            ->assertStatus(500)
            ->assertJson([
                'error' => [
                    'code' => 'INTERNAL_SERVER_ERROR',
                ],
            ]);
    }

    public function test_it_returns_a_json_forbidden_payload_for_authorization_exceptions(): void
    {
        Route::get('/api/v1/test-authorization-exception', function (): void {
            throw new AuthorizationException('forbidden');
        });

        $this->getJson('/api/v1/test-authorization-exception')
            ->assertForbidden()
            ->assertJson([
                'error' => [
                    'code' => 'FORBIDDEN',
                ],
            ]);
    }
}

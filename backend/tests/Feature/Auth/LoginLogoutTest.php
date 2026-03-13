<?php

namespace Tests\Feature\Auth;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoginLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_in_and_returns_a_token(): void
    {
        $business = Business::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $business->email,
            'password' => 'password123',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'business']);
    }

    public function test_it_logs_out_the_current_token(): void
    {
        $business = Business::factory()->create();
        Sanctum::actingAs($business);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertNoContent();
    }
}

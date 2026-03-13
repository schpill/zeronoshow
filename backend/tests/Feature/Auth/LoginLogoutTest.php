<?php

namespace Tests\Feature\Auth;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoginLogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('127.0.0.1');
        RateLimiter::clear('192.0.2.10');
        RateLimiter::clear('192.0.2.11');
    }

    public function test_it_logs_in_and_returns_a_token(): void
    {
        $business = Business::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.11'])->postJson('/api/v1/auth/login', [
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

    public function test_login_is_rate_limited_after_ten_attempts_per_ip(): void
    {
        $business = Business::factory()->create([
            'password' => Hash::make('password123'),
        ]);
        $client = $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10']);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $client->postJson('/api/v1/auth/login', [
                'email' => $business->email,
                'password' => 'wrong-password',
            ])->assertStatus(401);
        }

        $client->postJson('/api/v1/auth/login', [
            'email' => $business->email,
            'password' => 'wrong-password',
        ])
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }
}

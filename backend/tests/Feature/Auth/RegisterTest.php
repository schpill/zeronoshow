<?php

namespace Tests\Feature\Auth;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('127.0.0.1');
        RateLimiter::clear('192.0.2.20');
    }

    public function test_it_registers_a_business_and_returns_a_token(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.21'])->postJson('/api/v1/auth/register', [
            'name' => 'Gerald',
            'business_name' => 'Le Bistrot',
            'email' => 'owner@gmail.com',
            'phone' => '+33612345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('business.email', 'owner@gmail.com')
            ->assertJsonPath('business.subscription_status', 'trial')
            ->assertJsonStructure(['token', 'business' => ['id', 'name', 'email']]);

        $this->assertDatabaseHas('businesses', [
            'email' => 'owner@gmail.com',
            'name' => 'Le Bistrot',
        ]);
    }

    public function test_it_rejects_duplicate_email(): void
    {
        Business::factory()->create(['email' => 'owner@gmail.com']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.22'])->postJson('/api/v1/auth/register', [
            'name' => 'Gerald',
            'business_name' => 'Le Bistrot',
            'email' => 'owner@gmail.com',
            'phone' => '+33612345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_registration_is_rate_limited_after_five_attempts_per_ip(): void
    {
        $ip = sprintf('192.0.2.%d', random_int(30, 200));
        $client = $this->withServerVariables(['REMOTE_ADDR' => $ip]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $client->postJson('/api/v1/auth/register', [
                'name' => 'Gerald',
                'business_name' => 'Le Bistrot',
                'email' => sprintf('owner-%d@gmail.com', $attempt),
                'phone' => '+33612345678',
                'password' => 'short',
                'password_confirmation' => 'nope',
            ])->assertStatus(422);
        }

        $client->postJson('/api/v1/auth/register', [
            'name' => 'Gerald',
            'business_name' => 'Le Bistrot',
            'email' => 'owner-final@gmail.com',
            'phone' => '+33612345678',
            'password' => 'short',
            'password_confirmation' => 'nope',
        ])
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }
}

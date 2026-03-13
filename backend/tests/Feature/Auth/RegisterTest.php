<?php

namespace Tests\Feature\Auth;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_a_business_and_returns_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
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

        $response = $this->postJson('/api/v1/auth/register', [
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
        $client = $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.20']);

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

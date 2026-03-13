<?php

namespace Tests\Feature\Security;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_per_ip(): void
    {
        $business = Business::factory()->create([
            'password' => Hash::make('password123'),
        ]);
        $client = $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.31']);

        $throttled = false;

        for ($attempt = 0; $attempt < 11; $attempt++) {
            $response = $client->postJson('/api/v1/auth/login', [
                'email' => $business->email,
                'password' => 'wrong-password',
            ]);

            if ($response->status() === 429) {
                $response->assertHeader('Retry-After');
                $throttled = true;
                break;
            }
        }

        $this->assertTrue($throttled);
    }

    public function test_registration_is_rate_limited_per_ip(): void
    {
        $client = $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.32']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $client->postJson('/api/v1/auth/register', [
                'name' => 'Gerald',
                'business_name' => 'Le Bistrot',
                'email' => sprintf('rate-limit-%d@example.com', $attempt),
                'phone' => '+33612345678',
                'password' => 'short',
                'password_confirmation' => 'nope',
            ])->assertStatus(422);
        }

        $client->postJson('/api/v1/auth/register', [
            'name' => 'Gerald',
            'business_name' => 'Le Bistrot',
            'email' => 'rate-limit-final@example.com',
            'phone' => '+33612345678',
            'password' => 'short',
            'password_confirmation' => 'nope',
        ])->assertStatus(429);
    }

    public function test_reservation_creation_is_rate_limited_per_business(): void
    {
        Queue::fake();
        $business = Business::factory()->create();
        Sanctum::actingAs($business);

        for ($attempt = 0; $attempt < 60; $attempt++) {
            $this->postJson('/api/v1/reservations', [
                'customer_name' => sprintf('Marc %d', $attempt),
                'phone' => sprintf('+3362234%04d', $attempt),
                'scheduled_at' => now()->addDay()->toIso8601String(),
                'guests' => 2,
                'phone_verified' => false,
            ])->assertCreated();
        }

        $this->postJson('/api/v1/reservations', [
            'customer_name' => 'Marc Final',
            'phone' => '+33688888888',
            'scheduled_at' => now()->addDay()->toIso8601String(),
            'guests' => 2,
            'phone_verified' => false,
        ])->assertStatus(429);
    }
}

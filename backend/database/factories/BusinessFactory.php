<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password123'),
            'phone' => '+336'.fake()->numerify('########'),
            'timezone' => 'Europe/Paris',
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
            'leo_addon_active' => false,
            'leo_addon_stripe_item_id' => null,
            'voice_credit_cents' => 0,
            'voice_monthly_cap_cents' => 0,
            'voice_auto_renew' => false,
            'voice_last_renewed_at' => null,
            'voice_auto_call_enabled' => false,
            'voice_auto_call_score_threshold' => null,
            'voice_auto_call_min_party_size' => null,
            'voice_retry_count' => 2,
            'voice_retry_delay_minutes' => 10,
            'review_requests_enabled' => false,
            'review_platform' => 'google',
            'review_delay_hours' => 2,
            'google_place_id' => null,
            'tripadvisor_location_id' => null,
            'public_token' => Str::uuid(),
        ];
    }
}

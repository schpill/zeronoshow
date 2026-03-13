<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

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
        ];
    }
}

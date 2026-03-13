<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'customer_id' => Customer::factory(),
            'customer_name' => fake()->name(),
            'scheduled_at' => now()->addDay(),
            'guests' => fake()->numberBetween(1, 8),
            'notes' => fake()->optional()->sentence(),
            'status' => 'pending_verification',
            'phone_verified' => false,
            'confirmation_token' => (string) fake()->uuid(),
            'token_expires_at' => now()->addHours(20),
            'reminder_2h_sent' => false,
            'reminder_30m_sent' => false,
            'status_changed_at' => null,
        ];
    }
}

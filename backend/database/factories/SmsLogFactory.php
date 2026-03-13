<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Reservation;
use App\Models\SmsLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmsLog>
 */
class SmsLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'business_id' => Business::factory(),
            'phone' => '+336'.fake()->numerify('########'),
            'type' => 'verification',
            'body' => fake()->sentence(),
            'twilio_sid' => null,
            'status' => 'queued',
            'cost_eur' => null,
            'error_message' => null,
            'queued_at' => now(),
            'sent_at' => null,
            'delivered_at' => null,
            'created_at' => now(),
        ];
    }
}

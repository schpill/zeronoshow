<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ReviewRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewRequest>
 */
class ReviewRequestFactory extends Factory
{
    public function definition(): array
    {
        $reservation = Reservation::factory();

        return [
            'reservation_id' => $reservation,
            'business_id' => Business::factory(),
            'customer_id' => Customer::factory(),
            'channel' => 'sms',
            'platform' => 'google',
            'review_url' => 'https://search.google.com/local/writereview?placeid=ChIJ123456789',
            'short_code' => fake()->unique()->bothify('????????'),
            'status' => 'pending',
            'sent_at' => null,
            'clicked_at' => null,
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
        ];
    }
}

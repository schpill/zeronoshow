<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\LeoChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeoChannel>
 */
class LeoChannelFactory extends Factory
{
    protected $model = LeoChannel::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'channel' => 'telegram',
            'external_identifier' => fake()->numerify('#########'),
            'bot_name' => 'Léo',
            'is_active' => true,
        ];
    }
}

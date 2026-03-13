<?php

namespace Database\Factories;

use App\Models\LeoChannel;
use App\Models\LeoMessageLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeoMessageLog>
 */
class LeoMessageLogFactory extends Factory
{
    protected $model = LeoMessageLog::class;

    public function definition(): array
    {
        return [
            'channel_id' => LeoChannel::factory(),
            'direction' => fake()->randomElement(['inbound', 'outbound']),
            'sender_identifier' => fake()->numerify('#########'),
            'raw_message' => fake()->sentence(),
            'intent' => null,
            'tool_called' => null,
            'response_preview' => null,
            'tokens_used' => null,
            'latency_ms' => null,
            'created_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\ChannelTypeEnum;
use App\Enums\WaitlistStatusEnum;
use App\Models\Business;
use App\Models\WaitlistEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaitlistEntry>
 */
class WaitlistEntryFactory extends Factory
{
    protected $model = WaitlistEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'slot_date' => now()->addDays(1)->format('Y-m-d'),
            'slot_time' => '19:30:00',
            'client_name' => $this->faker->name(),
            'client_phone' => '+336'.$this->faker->numerify('########'),
            'party_size' => rand(1, 4),
            'priority_order' => 0,
            'status' => WaitlistStatusEnum::Pending,
            'channel' => ChannelTypeEnum::Sms,
        ];
    }
}

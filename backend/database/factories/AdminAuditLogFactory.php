<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\AdminAuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminAuditLog>
 */
class AdminAuditLogFactory extends Factory
{
    protected $model = AdminAuditLog::class;

    public function definition(): array
    {
        return [
            'admin_id' => Admin::factory(),
            'action' => 'extend_trial',
            'target_type' => 'Business',
            'target_id' => (string) fake()->uuid(),
            'payload' => ['source' => 'factory'],
            'ip_address' => fake()->ipv4(),
            'created_at' => now(),
        ];
    }
}

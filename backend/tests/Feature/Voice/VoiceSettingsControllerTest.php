<?php

namespace Tests\Feature\Voice;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_enables_auto_call_with_score_threshold(): void
    {
        $business = Business::factory()->create();

        $response = $this->actingAs($business)->patchJson('/api/v1/voice/settings', [
            'auto_call_enabled' => true,
            'score_threshold' => 42,
            'retry_count' => 2,
            'retry_delay_minutes' => 15,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.auto_call_enabled', true)
            ->assertJsonPath('data.auto_call_score_threshold', 42)
            ->assertJsonPath('data.retry_count', 2)
            ->assertJsonPath('data.retry_delay_minutes', 15);
    }

    public function test_update_requires_at_least_one_auto_call_criterion(): void
    {
        $business = Business::factory()->create();

        $response = $this->actingAs($business)->patchJson('/api/v1/voice/settings', [
            'auto_call_enabled' => true,
            'retry_count' => 2,
            'retry_delay_minutes' => 15,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['score_threshold']);
    }

    public function test_update_validates_retry_count(): void
    {
        $business = Business::factory()->create();

        $response = $this->actingAs($business)->patchJson('/api/v1/voice/settings', [
            'auto_call_enabled' => false,
            'retry_count' => 7,
            'retry_delay_minutes' => 15,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['retry_count']);
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_enables_auto_call_with_score_threshold(): void
    {
        $business = Business::factory()->create([
            'voice_auto_call_enabled' => false,
            'voice_retry_count' => 1,
            'voice_retry_delay_minutes' => 10,
        ]);

        $response = $this->actingAs($business)->patchJson('/api/v1/voice/settings', [
            'auto_call_enabled' => true,
            'score_threshold' => 72,
            'min_party_size' => null,
            'retry_count' => 3,
            'retry_delay_minutes' => 15,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.auto_call_enabled', true)
            ->assertJsonPath('data.auto_call_score_threshold', 72)
            ->assertJsonPath('data.auto_call_min_party_size', null)
            ->assertJsonPath('data.retry_count', 3)
            ->assertJsonPath('data.retry_delay_minutes', 15);
    }

    public function test_update_requires_at_least_one_auto_call_criterion_when_enabled(): void
    {
        $business = Business::factory()->create();

        $response = $this->actingAs($business)->patchJson('/api/v1/voice/settings', [
            'auto_call_enabled' => true,
            'score_threshold' => null,
            'min_party_size' => null,
            'retry_count' => 2,
            'retry_delay_minutes' => 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['auto_call_enabled']);
    }

    public function test_update_rejects_invalid_retry_count(): void
    {
        $business = Business::factory()->create();

        $response = $this->actingAs($business)->patchJson('/api/v1/voice/settings', [
            'auto_call_enabled' => false,
            'score_threshold' => null,
            'min_party_size' => null,
            'retry_count' => 6,
            'retry_delay_minutes' => 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['retry_count']);
    }
}

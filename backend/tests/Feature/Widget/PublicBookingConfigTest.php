<?php

namespace Tests\Feature\Widget;

use App\Models\Business;
use App\Models\WidgetSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBookingConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_public_config_for_valid_token(): void
    {
        $business = Business::factory()->create();
        WidgetSetting::create([
            'business_id' => $business->id,
            'accent_colour' => '#6366f1',
            'is_enabled' => true,
        ]);

        $response = $this->getJson('/api/v1/public/widget/'.$business->public_token.'/config');

        $response->assertOk()
            ->assertJsonPath('config.accent_colour', '#6366f1')
            ->assertJsonPath('config.is_enabled', true)
            ->assertJsonPath('config.max_party_size', 20);
    }

    public function test_it_returns_404_for_unknown_token(): void
    {
        $response = $this->getJson('/api/v1/public/widget/00000000-0000-0000-0000-000000000000/config');

        $response->assertNotFound();
    }

    public function test_disabled_widget_returns_423_locked(): void
    {
        $business = Business::factory()->create();
        WidgetSetting::create([
            'business_id' => $business->id,
            'is_enabled' => false,
        ]);

        $response = $this->getJson('/api/v1/public/widget/'.$business->public_token.'/config');

        $response->assertStatus(423);
    }

    public function test_logo_url_and_accent_colour_are_included_in_response(): void
    {
        $business = Business::factory()->create();
        WidgetSetting::create([
            'business_id' => $business->id,
            'logo_url' => 'https://example.com/logo.png',
            'accent_colour' => '#ff0000',
            'is_enabled' => true,
        ]);

        $response = $this->getJson('/api/v1/public/widget/'.$business->public_token.'/config');

        $response->assertOk()
            ->assertJsonPath('config.logo_url', 'https://example.com/logo.png')
            ->assertJsonPath('config.accent_colour', '#ff0000');
    }
}

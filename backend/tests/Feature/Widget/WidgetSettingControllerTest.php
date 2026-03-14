<?php

namespace Tests\Feature\Widget;

use App\Models\Business;
use App\Models\WidgetSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WidgetSettingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_cannot_access_settings(): void
    {
        $business = Business::factory()->create();

        $this->getJson('/api/v1/businesses/'.$business->id.'/widget')
            ->assertStatus(401);
    }

    public function test_authenticated_business_owner_can_show_settings(): void
    {
        $business = Business::factory()->create();
        WidgetSetting::create([
            'business_id' => $business->id,
            'accent_colour' => '#ff0000',
        ]);
        Sanctum::actingAs($business);

        $response = $this->getJson('/api/v1/businesses/'.$business->id.'/widget');

        $response->assertOk()
            ->assertJsonPath('setting.accent_colour', '#ff0000');
    }

    public function test_authenticated_can_update_logo_url_and_accent_colour(): void
    {
        $business = Business::factory()->create();
        Sanctum::actingAs($business);

        $response = $this->patchJson('/api/v1/businesses/'.$business->id.'/widget', [
            'logo_url' => 'https://example.com/logo.png',
            'accent_colour' => '#ff0000',
        ]);

        $response->assertOk()
            ->assertJsonPath('setting.logo_url', 'https://example.com/logo.png')
            ->assertJsonPath('setting.accent_colour', '#ff0000');
    }

    public function test_invalid_accent_colour_is_rejected(): void
    {
        $business = Business::factory()->create();
        Sanctum::actingAs($business);

        $response = $this->patchJson('/api/v1/businesses/'.$business->id.'/widget', [
            'accent_colour' => 'not-a-color',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['accent_colour']);
    }

    public function test_embed_url_and_booking_url_are_computed_correctly(): void
    {
        $business = Business::factory()->create();
        WidgetSetting::create(['business_id' => $business->id]);
        Sanctum::actingAs($business);

        $response = $this->getJson('/api/v1/businesses/'.$business->id.'/widget');

        $baseUrl = config('app.url');
        $response->assertOk()
            ->assertJsonPath('setting.embed_url', $baseUrl.'/widget/'.$business->public_token)
            ->assertJsonPath('setting.booking_url', $baseUrl.'/widget/'.$business->public_token);
    }
}

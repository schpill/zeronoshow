<?php

namespace Tests\Feature\Widget;

use App\Models\Business;
use App\Models\WidgetSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IframeHeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_widget_routes_return_allowall_x_frame_options(): void
    {
        $business = Business::factory()->create();
        WidgetSetting::create([
            'business_id' => $business->id,
            'is_enabled' => true,
        ]);

        $response = $this->getJson('/api/v1/public/widget/'.$business->public_token.'/config');

        $response->assertOk();
        $this->assertSame('ALLOWALL', $response->headers->get('X-Frame-Options'));
    }

    public function test_authenticated_dashboard_routes_do_not_have_allowall_header(): void
    {
        $business = Business::factory()->create();
        Sanctum::actingAs($business);

        $response = $this->getJson('/api/v1/businesses/'.$business->id.'/widget');

        $response->assertOk();
        $this->assertNotSame('ALLOWALL', $response->headers->get('X-Frame-Options'));
    }

    public function test_other_public_routes_do_not_have_allowall_header(): void
    {
        $response = $this->getJson('/api/v1/health');

        $this->assertNotSame('ALLOWALL', $response->headers->get('X-Frame-Options'));
    }
}

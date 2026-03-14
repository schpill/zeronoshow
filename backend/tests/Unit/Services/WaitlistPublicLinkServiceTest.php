<?php

namespace Tests\Unit\Services;

use App\Models\Business;
use App\Services\WaitlistPublicLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaitlistPublicLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    private WaitlistPublicLinkService $service;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WaitlistPublicLinkService;
        $this->business = Business::factory()->create();
    }

    public function test_can_generate_token(): void
    {
        $token = $this->service->generateToken($this->business);

        $this->assertNotNull($token);
        $this->assertEquals($token, $this->business->fresh()->waitlist_public_token);
    }

    public function test_can_invalidate_token(): void
    {
        $this->business->update(['waitlist_public_token' => 'some-token']);

        $this->service->invalidateToken($this->business);

        $this->assertNull($this->business->fresh()->waitlist_public_token);
    }

    public function test_get_public_url_returns_null_if_no_token(): void
    {
        $this->assertNull($this->service->getPublicUrl($this->business));
    }

    public function test_get_public_url_returns_full_url_with_token(): void
    {
        $this->business->update(['waitlist_public_token' => 'my-token']);

        $url = $this->service->getPublicUrl($this->business);

        $this->assertStringContainsString('/join/my-token', $url);
    }
}

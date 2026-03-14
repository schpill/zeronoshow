<?php

namespace Tests\Unit\Leo;

use App\Models\Business;
use App\Models\LeoChannel;
use App\Leo\Tools\LeoBusinessResolver;
use App\Leo\Tools\LeoSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeoBusinessResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_none_when_no_active_channel_matches(): void
    {
        $business = Business::factory()->create(['leo_addon_active' => false]);
        $business->leoChannel()->create([
            'channel' => 'telegram',
            'external_identifier' => '123456789',
            'bot_name' => 'Léo',
            'is_active' => true,
        ]);

        $resolver = new LeoBusinessResolver(new LeoSessionService);
        $result = $resolver->resolve('telegram', '123456789');

        $this->assertSame('none', $result['status']);
        $this->assertNull($result['channel']);
        $this->assertCount(0, $result['channels']);
    }

    public function test_it_returns_single_when_one_active_channel_matches(): void
    {
        $business = Business::factory()->create(['leo_addon_active' => true]);
        $channel = LeoChannel::factory()->create([
            'business_id' => $business->id,
            'channel' => 'telegram',
            'external_identifier' => '123456789',
            'is_active' => true,
        ]);

        $resolver = new LeoBusinessResolver(new LeoSessionService);
        $result = $resolver->resolve('telegram', '123456789');

        $this->assertSame('single', $result['status']);
        $this->assertSame($channel->id, $result['channel']?->id);
        $this->assertCount(1, $result['channels']);
    }

    public function test_it_returns_multiple_when_several_businesses_share_the_same_sender(): void
    {
        $firstBusiness = Business::factory()->create(['leo_addon_active' => true]);
        $secondBusiness = Business::factory()->create(['leo_addon_active' => true]);

        LeoChannel::factory()->create([
            'business_id' => $firstBusiness->id,
            'external_identifier' => '123456789',
            'is_active' => true,
        ]);
        LeoChannel::factory()->create([
            'business_id' => $secondBusiness->id,
            'external_identifier' => '123456789',
            'is_active' => true,
        ]);

        $resolver = new LeoBusinessResolver(new LeoSessionService);
        $result = $resolver->resolve('telegram', '123456789');

        $this->assertSame('multiple', $result['status']);
        $this->assertNull($result['channel']);
        $this->assertCount(2, $result['channels']);
    }

    public function test_it_uses_the_session_to_resolve_one_business_among_multiple_matches(): void
    {
        $firstBusiness = Business::factory()->create(['leo_addon_active' => true]);
        $secondBusiness = Business::factory()->create(['leo_addon_active' => true]);

        $firstChannel = LeoChannel::factory()->create([
            'business_id' => $firstBusiness->id,
            'external_identifier' => '123456789',
            'is_active' => true,
        ]);
        LeoChannel::factory()->create([
            'business_id' => $secondBusiness->id,
            'external_identifier' => '123456789',
            'is_active' => true,
        ]);

        $sessionService = new LeoSessionService;
        $sessionService->set($firstChannel->id, '123456789', $firstBusiness->id, 300);

        $resolver = new LeoBusinessResolver($sessionService);
        $result = $resolver->resolve('telegram', '123456789');

        $this->assertSame('single', $result['status']);
        $this->assertSame($firstChannel->id, $result['channel']?->id);
    }
}

<?php

namespace Tests\Feature\Leo;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeoChannelCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_null_when_no_channel_exists(): void
    {
        $business = Business::factory()->create([
            'leo_addon_active' => true,
        ]);

        Sanctum::actingAs($business);

        $this->getJson('/api/v1/leo/channels')
            ->assertOk()
            ->assertJson([
                'channel' => null,
            ]);
    }

    public function test_store_creates_a_channel_when_addon_is_active(): void
    {
        $business = Business::factory()->create([
            'leo_addon_active' => true,
        ]);

        Sanctum::actingAs($business);

        $this->postJson('/api/v1/leo/channels', [
            'channel' => 'telegram',
            'external_identifier' => '123456789',
            'bot_name' => 'Léo',
        ])
            ->assertCreated()
            ->assertJsonPath('channel.channel', 'telegram')
            ->assertJsonPath('channel.external_identifier_masked', '***6789');

        $this->assertDatabaseHas('leo_channels', [
            'business_id' => $business->id,
            'channel' => 'telegram',
            'external_identifier' => '123456789',
            'bot_name' => 'Léo',
            'is_active' => true,
        ]);
    }

    public function test_store_returns_payment_required_when_addon_is_inactive(): void
    {
        $business = Business::factory()->create([
            'leo_addon_active' => false,
        ]);

        Sanctum::actingAs($business);

        $this->postJson('/api/v1/leo/channels', [
            'channel' => 'telegram',
            'external_identifier' => '123456789',
            'bot_name' => 'Léo',
        ])->assertStatus(402);
    }

    public function test_store_returns_conflict_when_channel_already_exists_for_business(): void
    {
        $business = Business::factory()->create([
            'leo_addon_active' => true,
        ]);

        Sanctum::actingAs($business);

        $this->postJson('/api/v1/leo/channels', [
            'channel' => 'telegram',
            'external_identifier' => '123456789',
            'bot_name' => 'Léo',
        ])->assertCreated();

        $this->postJson('/api/v1/leo/channels', [
            'channel' => 'telegram',
            'external_identifier' => '987654321',
            'bot_name' => 'Léo 2',
        ])->assertStatus(409);
    }

    public function test_update_only_allows_bot_name_and_is_active(): void
    {
        $business = Business::factory()->create([
            'leo_addon_active' => true,
        ]);

        Sanctum::actingAs($business);

        $created = $this->postJson('/api/v1/leo/channels', [
            'channel' => 'telegram',
            'external_identifier' => '123456789',
            'bot_name' => 'Léo',
        ])->assertCreated();

        $channelId = (string) $created->json('channel.id');

        $this->patchJson("/api/v1/leo/channels/{$channelId}", [
            'bot_name' => 'Léo Premium',
            'is_active' => false,
            'channel' => 'sms',
        ])
            ->assertOk()
            ->assertJsonPath('channel.bot_name', 'Léo Premium')
            ->assertJsonPath('channel.is_active', false)
            ->assertJsonMissingPath('channel.external_identifier');

        $this->assertDatabaseHas('leo_channels', [
            'id' => $channelId,
            'channel' => 'telegram',
            'bot_name' => 'Léo Premium',
            'is_active' => false,
        ]);
    }

    public function test_destroy_deletes_channel_and_allows_recreation(): void
    {
        $business = Business::factory()->create([
            'leo_addon_active' => true,
        ]);

        Sanctum::actingAs($business);

        $created = $this->postJson('/api/v1/leo/channels', [
            'channel' => 'telegram',
            'external_identifier' => '123456789',
            'bot_name' => 'Léo',
        ])->assertCreated();

        $channelId = (string) $created->json('channel.id');

        $this->deleteJson("/api/v1/leo/channels/{$channelId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('leo_channels', [
            'id' => $channelId,
        ]);

        $this->postJson('/api/v1/leo/channels', [
            'channel' => 'telegram',
            'external_identifier' => '987654321',
            'bot_name' => 'Léo Bis',
        ])->assertCreated();
    }
}

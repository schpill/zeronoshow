<?php

namespace Tests\Feature\Leo;

use App\Models\Business;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeoAddonBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_activate_creates_a_stripe_subscription_item_and_marks_the_addon_active(): void
    {
        config()->set('leo.stripe.price_id', 'price_leo');

        $business = Business::factory()->create([
            'stripe_subscription_id' => 'sub_active',
            'leo_addon_active' => false,
            'leo_addon_stripe_item_id' => null,
        ]);

        $this->instance(StripeService::class, new class extends StripeService
        {
            public function createSubscriptionItem(string $subscriptionId, string $priceId): array
            {
                test()->assertSame('sub_active', $subscriptionId);
                test()->assertSame('price_leo', $priceId);

                return ['id' => 'si_leo_123'];
            }
        });

        Sanctum::actingAs($business);

        $this->postJson('/api/v1/leo/addon/activate')
            ->assertOk()
            ->assertJson([
                'activated' => true,
                'checkout_url' => null,
            ]);

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'leo_addon_active' => true,
            'leo_addon_stripe_item_id' => 'si_leo_123',
        ]);
    }

    public function test_activate_returns_payment_required_when_business_has_no_active_stripe_subscription(): void
    {
        $business = Business::factory()->create([
            'stripe_subscription_id' => null,
            'leo_addon_active' => false,
        ]);

        Sanctum::actingAs($business);

        $this->postJson('/api/v1/leo/addon/activate')
            ->assertStatus(402)
            ->assertJsonPath('message', 'Aucun abonnement Stripe actif n’a ete trouve.');
    }

    public function test_activate_returns_payment_required_when_business_plan_is_not_active(): void
    {
        $business = Business::factory()->create([
            'stripe_subscription_id' => 'sub_cancelled',
            'subscription_status' => 'cancelled',
            'leo_addon_active' => false,
        ]);

        Sanctum::actingAs($business);

        $this->postJson('/api/v1/leo/addon/activate')
            ->assertStatus(402)
            ->assertJsonPath('message', 'Aucun abonnement Stripe actif n’a ete trouve.');
    }

    public function test_activate_uses_the_cached_leo_price_id_when_config_is_empty(): void
    {
        config()->set('leo.stripe.price_id', null);
        Cache::forever('leo:stripe:price_id', 'price_cached_leo');

        $business = Business::factory()->create([
            'subscription_status' => 'active',
            'stripe_subscription_id' => 'sub_active',
            'leo_addon_active' => false,
            'leo_addon_stripe_item_id' => null,
        ]);

        $this->instance(StripeService::class, new class extends StripeService
        {
            public function createSubscriptionItem(string $subscriptionId, string $priceId): array
            {
                test()->assertSame('price_cached_leo', $priceId);

                return ['id' => 'si_cached_123'];
            }
        });

        Sanctum::actingAs($business);

        $this->postJson('/api/v1/leo/addon/activate')
            ->assertOk()
            ->assertJsonPath('activated', true);
    }

    public function test_deactivate_deletes_the_stripe_item_and_disables_the_addon_and_channels(): void
    {
        $business = Business::factory()->create([
            'stripe_subscription_id' => 'sub_active',
            'leo_addon_active' => true,
            'leo_addon_stripe_item_id' => 'si_leo_456',
        ]);

        $business->leoChannel()->create([
            'channel' => 'telegram',
            'external_identifier' => '123456789',
            'bot_name' => 'Léo',
            'is_active' => true,
        ]);

        $this->instance(StripeService::class, new class extends StripeService
        {
            public function deleteSubscriptionItem(string $subscriptionItemId): void
            {
                test()->assertSame('si_leo_456', $subscriptionItemId);
            }
        });

        Sanctum::actingAs($business);

        $this->postJson('/api/v1/leo/addon/deactivate')
            ->assertNoContent();

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'leo_addon_active' => false,
            'leo_addon_stripe_item_id' => null,
        ]);

        $this->assertDatabaseHas('leo_channels', [
            'business_id' => $business->id,
            'is_active' => false,
        ]);
    }

    public function test_status_returns_the_current_addon_state(): void
    {
        $business = Business::factory()->create([
            'leo_addon_active' => true,
            'leo_addon_stripe_item_id' => 'si_leo_status',
        ]);

        Sanctum::actingAs($business);

        $this->getJson('/api/v1/leo/addon-status')
            ->assertOk()
            ->assertJson([
                'active' => true,
                'stripe_item_id' => 'si_leo_status',
            ]);
    }
}

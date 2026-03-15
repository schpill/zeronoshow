<?php

use App\Models\Admin;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\SmsLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('index returns paginated list', function () {
    $admin = Admin::factory()->create();

    Business::factory()->count(21)->create();

    Sanctum::actingAs($admin, ['admin']);

    $this->getJson('/api/v1/admin/businesses')
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('meta.total', 21);
});

it('search filters by name', function () {
    $admin = Admin::factory()->create();

    Business::factory()->create(['name' => 'Alpha Bistro']);
    Business::factory()->create(['name' => 'Beta Cafe']);

    Sanctum::actingAs($admin, ['admin']);

    $this->getJson('/api/v1/admin/businesses?search=alpha')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Bistro');
});

it('search filters by email', function () {
    $admin = Admin::factory()->create();

    Business::factory()->create(['email' => 'match@example.com']);
    Business::factory()->create(['email' => 'other@example.com']);

    Sanctum::actingAs($admin, ['admin']);

    $this->getJson('/api/v1/admin/businesses?search=match@example.com')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.email', 'match@example.com');
});

it('status filter works', function () {
    $admin = Admin::factory()->create();

    Business::factory()->create(['subscription_status' => 'trial']);
    Business::factory()->create(['subscription_status' => 'active']);

    Sanctum::actingAs($admin, ['admin']);

    $this->getJson('/api/v1/admin/businesses?status=active')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.subscription_status', 'active');
});

it('show returns reservations and sms summary', function () {
    $admin = Admin::factory()->create();
    $business = Business::factory()->create();
    $customer = Customer::factory()->create();

    $reservations = Reservation::factory()->count(2)->create([
        'business_id' => $business->id,
        'customer_id' => $customer->id,
    ]);

    SmsLog::factory()->create([
        'business_id' => $business->id,
        'reservation_id' => $reservations[0]->id,
        'status' => 'delivered',
        'cost_eur' => 0.12,
    ]);
    SmsLog::factory()->create([
        'business_id' => $business->id,
        'reservation_id' => $reservations[1]->id,
        'status' => 'failed',
        'cost_eur' => 0.08,
    ]);

    Sanctum::actingAs($admin, ['admin']);

    $this->getJson("/api/v1/admin/businesses/{$business->id}")
        ->assertOk()
        ->assertJsonPath('business.id', $business->id)
        ->assertJsonCount(2, 'recent_reservations')
        ->assertJsonPath('sms_log_summary.total_sent', 2)
        ->assertJsonPath('sms_log_summary.delivered', 1)
        ->assertJsonPath('sms_log_summary.failed', 1)
        ->assertJsonPath('sms_log_summary.cost', 0.2);
});

it('extend-trial adds days', function () {
    $admin = Admin::factory()->create();
    $business = Business::factory()->create([
        'subscription_status' => 'trial',
        'trial_ends_at' => now()->addDays(3),
    ]);

    Sanctum::actingAs($admin, ['admin']);

    $this->patchJson("/api/v1/admin/businesses/{$business->id}/extend-trial", [
        'days' => 7,
    ])->assertOk()
        ->assertJsonPath('trial_ends_at', $business->trial_ends_at?->copy()->addDays(7)->toIso8601String());
});

it('extend-trial on paid business returns 422', function () {
    $admin = Admin::factory()->create();
    $business = Business::factory()->create([
        'subscription_status' => 'active',
    ]);

    Sanctum::actingAs($admin, ['admin']);

    $this->patchJson("/api/v1/admin/businesses/{$business->id}/extend-trial", [
        'days' => 7,
    ])->assertStatus(422)
        ->assertJsonPath('message', 'Business is not in trial.');
});

it('cancel-subscription sets status and logs audit', function () {
    $admin = Admin::factory()->create();
    $business = Business::factory()->create([
        'subscription_status' => 'active',
    ]);

    Sanctum::actingAs($admin, ['admin']);

    $this->patchJson("/api/v1/admin/businesses/{$business->id}/cancel-subscription", [
        'reason' => 'Manual cancellation',
    ])->assertOk()
        ->assertJsonPath('subscription_status', 'cancelled');

    $this->assertDatabaseHas('admin_audit_logs', [
        'admin_id' => $admin->id,
        'action' => 'cancel_subscription',
        'target_type' => 'Business',
        'target_id' => $business->id,
    ]);
});

it('impersonate returns token with 15min ttl', function () {
    $admin = Admin::factory()->create();
    $business = Business::factory()->create([
        'name' => 'Impersonated Bistro',
    ]);

    Sanctum::actingAs($admin, ['admin']);

    $response = $this->postJson("/api/v1/admin/businesses/{$business->id}/impersonate")
        ->assertOk()
        ->assertJsonPath('business_name', 'Impersonated Bistro');

    $plainTextToken = $response->json('impersonation_token');
    $tokenId = explode('|', $plainTextToken)[0];

    $token = PersonalAccessToken::query()->findOrFail($tokenId);

    expect($token->tokenable_type)->toBe(Business::class)
        ->and($token->can('impersonate'))->toBeTrue()
        ->and($token->expires_at?->between(now()->addMinutes(14), now()->addMinutes(16)))->toBeTrue();
});

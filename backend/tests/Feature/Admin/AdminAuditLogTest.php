<?php

use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('all write actions create audit log entry', function () {
    $admin = Admin::factory()->create();
    $business = Business::factory()->create([
        'subscription_status' => 'trial',
        'trial_ends_at' => now()->addDays(5),
    ]);

    Sanctum::actingAs($admin, ['admin']);

    $this->patchJson("/api/v1/admin/businesses/{$business->id}/extend-trial", [
        'days' => 5,
    ])->assertOk();

    $this->patchJson("/api/v1/admin/businesses/{$business->id}/cancel-subscription", [
        'reason' => 'Operator request',
    ])->assertOk();

    $this->postJson("/api/v1/admin/businesses/{$business->id}/impersonate")
        ->assertOk();

    expect(AdminAuditLog::query()->pluck('action')->all())
        ->toBe(['extend_trial', 'cancel_subscription', 'impersonate']);
});

it('audit log index is paginated', function () {
    $admin = Admin::factory()->create();
    $business = Business::factory()->create();

    AdminAuditLog::factory()->count(55)->create([
        'admin_id' => $admin->id,
        'target_type' => 'Business',
        'target_id' => $business->id,
    ]);

    Sanctum::actingAs($admin, ['admin']);

    $this->getJson('/api/v1/admin/audit-logs')
        ->assertOk()
        ->assertJsonCount(50, 'data')
        ->assertJsonPath('meta.total', 55);
});

it('filter by action works', function () {
    $admin = Admin::factory()->create();
    $business = Business::factory()->create();

    AdminAuditLog::factory()->create([
        'admin_id' => $admin->id,
        'action' => 'extend_trial',
        'target_type' => 'Business',
        'target_id' => $business->id,
    ]);
    AdminAuditLog::factory()->create([
        'admin_id' => $admin->id,
        'action' => 'impersonate',
        'target_type' => 'Business',
        'target_id' => $business->id,
    ]);

    Sanctum::actingAs($admin, ['admin']);

    $this->getJson('/api/v1/admin/audit-logs?action=impersonate')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.action', 'impersonate');
});

it('filter by target_type works', function () {
    $admin = Admin::factory()->create();
    $business = Business::factory()->create();

    AdminAuditLog::factory()->create([
        'admin_id' => $admin->id,
        'action' => 'extend_trial',
        'target_type' => 'Business',
        'target_id' => $business->id,
    ]);
    AdminAuditLog::factory()->create([
        'admin_id' => $admin->id,
        'action' => 'other',
        'target_type' => 'System',
        'target_id' => (string) str()->uuid(),
    ]);

    Sanctum::actingAs($admin, ['admin']);

    $this->getJson('/api/v1/admin/audit-logs?target_type=Business')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.target_type', 'Business');
});

it('filter by date range works', function () {
    $admin = Admin::factory()->create();
    $business = Business::factory()->create();

    AdminAuditLog::factory()->create([
        'admin_id' => $admin->id,
        'action' => 'extend_trial',
        'target_type' => 'Business',
        'target_id' => $business->id,
        'created_at' => now()->subDays(10),
    ]);

    AdminAuditLog::factory()->create([
        'admin_id' => $admin->id,
        'action' => 'impersonate',
        'target_type' => 'Business',
        'target_id' => $business->id,
        'created_at' => now()->subDay(),
    ]);

    Sanctum::actingAs($admin, ['admin']);

    $this->getJson('/api/v1/admin/audit-logs?date_from='.now()->subDays(2)->toDateString().'&date_to='.now()->toDateString())
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.action', 'impersonate');
});

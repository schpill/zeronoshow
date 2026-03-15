<?php

use App\Models\Admin;
use App\Models\Business;
use App\Models\Reservation;
use App\Models\SmsLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('stats endpoint returns all expected fields', function () {
    $admin = Admin::factory()->create();

    $activeTrialBusiness = Business::factory()->create([
        'subscription_status' => 'trial',
        'trial_ends_at' => now()->addDays(4),
    ]);
    $expiredTrialBusiness = Business::factory()->create([
        'subscription_status' => 'trial',
        'trial_ends_at' => now()->subDay(),
    ]);
    $paidBusiness = Business::factory()->create([
        'subscription_status' => 'active',
    ]);
    $cancelledBusiness = Business::factory()->create([
        'subscription_status' => 'cancelled',
    ]);

    SmsLog::factory()->create([
        'business_id' => $activeTrialBusiness->id,
        'reservation_id' => Reservation::factory()->create(['business_id' => $activeTrialBusiness->id])->id,
        'status' => 'delivered',
        'cost_eur' => 0.12,
        'created_at' => now()->startOfMonth()->addDay(),
    ]);
    SmsLog::factory()->create([
        'business_id' => $expiredTrialBusiness->id,
        'reservation_id' => Reservation::factory()->create(['business_id' => $expiredTrialBusiness->id])->id,
        'status' => 'failed',
        'cost_eur' => 0.08,
        'created_at' => now()->startOfMonth()->addDays(2),
    ]);

    DB::table('failed_jobs')->insert([
        'uuid' => (string) str()->uuid(),
        'connection' => 'redis',
        'queue' => 'default',
        'payload' => '{}',
        'exception' => 'Test failure',
        'failed_at' => now(),
    ]);

    Sanctum::actingAs($admin, ['admin']);

    $this->getJson('/api/v1/admin/stats')
        ->assertOk()
        ->assertJsonPath('total_businesses', 4)
        ->assertJsonPath('active_trials', 1)
        ->assertJsonPath('expired_trials', 1)
        ->assertJsonPath('paid_subscriptions', 1)
        ->assertJsonPath('cancelled_subscriptions', 1)
        ->assertJsonPath('sms_sent_this_month', 2)
        ->assertJsonPath('sms_cost_this_month', 0.2)
        ->assertJsonPath('failed_jobs_count', 1);
});

it('sms_sent_this_month counts only current month', function () {
    $admin = Admin::factory()->create();

    SmsLog::factory()->create([
        'created_at' => now()->startOfMonth()->addHour(),
    ]);
    SmsLog::factory()->create([
        'created_at' => now()->subMonth()->endOfMonth(),
    ]);

    Sanctum::actingAs($admin, ['admin']);

    $this->getJson('/api/v1/admin/stats')
        ->assertOk()
        ->assertJsonPath('sms_sent_this_month', 1);
});

it('failed_jobs_count reflects actual count', function () {
    $admin = Admin::factory()->create();

    DB::table('failed_jobs')->insert([
        [
            'uuid' => (string) str()->uuid(),
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'First failure',
            'failed_at' => now(),
        ],
        [
            'uuid' => (string) str()->uuid(),
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Second failure',
            'failed_at' => now(),
        ],
    ]);

    Sanctum::actingAs($admin, ['admin']);

    $this->getJson('/api/v1/admin/stats')
        ->assertOk()
        ->assertJsonPath('failed_jobs_count', 2);
});

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $trialEndsAtDefault = match (DB::getDriverName()) {
            'pgsql' => DB::raw("(CURRENT_TIMESTAMP + INTERVAL '14 days')"),
            'sqlite' => DB::raw("(datetime('now', '+14 days'))"),
            default => DB::raw('CURRENT_TIMESTAMP'),
        };

        Schema::create('businesses', function (Blueprint $table) use ($trialEndsAtDefault): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone', 20)->nullable();
            $table->string('timezone', 50)->default('Europe/Paris');
            $table->string('subscription_status')->default('trial');
            $table->timestampTz('trial_ends_at')->default($trialEndsAtDefault);
            $table->string('stripe_customer_id', 100)->nullable();
            $table->string('stripe_subscription_id', 100)->nullable();
            $table->rememberToken();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};

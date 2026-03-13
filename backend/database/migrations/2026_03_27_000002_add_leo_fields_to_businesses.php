<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            $table->boolean('leo_addon_active')->default(false)->after('stripe_subscription_id');
            $table->string('leo_addon_stripe_item_id', 100)->nullable()->after('leo_addon_active');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            $table->dropColumn(['leo_addon_active', 'leo_addon_stripe_item_id']);
        });
    }
};

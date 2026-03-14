<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('waitlist_enabled')->default(false)->index();
            $table->smallInteger('waitlist_notification_window_minutes')->default(15);
            $table->string('waitlist_public_token', 64)->unique()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['waitlist_enabled', 'waitlist_notification_window_minutes', 'waitlist_public_token']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            $table->boolean('review_requests_enabled')->default(false)->after('waitlist_public_token');
            $table->string('review_platform', 20)->default('google')->after('review_requests_enabled');
            $table->unsignedSmallInteger('review_delay_hours')->default(2)->after('review_platform');
            $table->string('google_place_id', 255)->nullable()->after('review_delay_hours');
            $table->string('tripadvisor_location_id', 255)->nullable()->after('google_place_id');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            $table->dropColumn([
                'review_requests_enabled',
                'review_platform',
                'review_delay_hours',
                'google_place_id',
                'tripadvisor_location_id',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widget_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete()->unique();
            $table->string('logo_url', 500)->nullable();
            $table->char('accent_colour', 7)->default('#6366f1');
            $table->unsignedSmallInteger('max_party_size')->default(20);
            $table->unsignedSmallInteger('advance_booking_days')->default(60);
            $table->unsignedSmallInteger('same_day_cutoff_minutes')->default(60);
            $table->boolean('is_enabled')->default(true);
            $table->timestampsTz();

            $table->index('business_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_settings');
    }
};

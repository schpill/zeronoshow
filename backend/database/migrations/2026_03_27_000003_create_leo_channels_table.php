<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leo_channels', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('channel', 50)->default('telegram');
            $table->string('external_identifier');
            $table->string('bot_name', 100)->default('Léo');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique('business_id');
            $table->index('channel');
            $table->index('external_identifier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leo_channels');
    }
};

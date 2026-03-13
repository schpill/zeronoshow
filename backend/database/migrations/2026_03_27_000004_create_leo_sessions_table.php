<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leo_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('channel_id')->constrained('leo_channels')->cascadeOnDelete();
            $table->string('sender_identifier');
            $table->foreignUuid('active_business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->timestampTz('expires_at');
            $table->timestampsTz();

            $table->unique(['channel_id', 'sender_identifier']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leo_sessions');
    }
};

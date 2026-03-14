<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_call_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->string('to_phone', 20);
            $table->unsignedSmallInteger('attempt_number')->default(1);
            $table->string('status', 20)->default('initiated');
            $table->char('dtmf_response', 1)->nullable();
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            $table->unsignedSmallInteger('cost_cents')->nullable();
            $table->string('twilio_call_sid', 34)->nullable()->unique();
            $table->timestampsTz();

            $table->index('reservation_id');
            $table->index(['business_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_call_logs');
    }
};

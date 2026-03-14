<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversation_windows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('channel_id')->constrained('leo_channels')->cascadeOnDelete();
            $table->string('contact_phone', 50);
            $table->enum('conversation_type', ['service', 'utility'])->default('service');
            $table->timestampTz('opened_at');
            $table->timestampTz('expires_at');
            $table->integer('cost_cents')->default(0);
            $table->timestampTz('created_at')->nullable();

            $table->index(['channel_id', 'contact_phone', 'conversation_type', 'expires_at'], 'wa_conv_lookup');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversation_windows');
    }
};

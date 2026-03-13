<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leo_message_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('channel_id')->nullable()->constrained('leo_channels')->nullOnDelete();
            $table->string('direction', 20);
            $table->string('sender_identifier');
            $table->text('raw_message');
            $table->string('intent', 100)->nullable();
            $table->string('tool_called', 100)->nullable();
            $table->string('response_preview', 500)->nullable();
            $table->integer('tokens_used')->nullable();
            $table->integer('latency_ms')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['channel_id', 'created_at']);
            $table->index('direction');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leo_message_logs');
    }
};

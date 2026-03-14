<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('reservation_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 20)->default('sms');
            $table->string('platform', 20)->default('google');
            $table->text('review_url');
            $table->string('short_code', 12)->unique();
            $table->string('status', 20)->default('pending');
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('clicked_at')->nullable();
            $table->timestampTz('expires_at');
            $table->timestampTz('created_at')->nullable();

            $table->index(['business_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_requests');
    }
};

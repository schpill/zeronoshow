<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers')->restrictOnDelete();
            $table->string('customer_name');
            $table->timestampTz('scheduled_at');
            $table->unsignedSmallInteger('guests')->default(1);
            $table->text('notes')->nullable();
            $table->string('status')->default('pending_verification');
            $table->boolean('phone_verified')->default(false);
            $table->uuid('confirmation_token')->nullable()->unique();
            $table->timestampTz('token_expires_at')->nullable();
            $table->boolean('reminder_2h_sent')->default(false);
            $table->boolean('reminder_30m_sent')->default(false);
            $table->timestampTz('status_changed_at')->nullable();
            $table->timestampsTz();

            $table->index(['business_id', 'scheduled_at']);
            $table->index(['status', 'reminder_2h_sent', 'reminder_30m_sent', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};

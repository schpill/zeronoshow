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
        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->onDelete('cascade');
            $table->date('slot_date')->index();
            $table->time('slot_time');
            $table->string('client_name', 150);
            $table->string('client_phone', 20);
            $table->smallInteger('party_size')->default(1);
            $table->integer('priority_order')->default(0);
            $table->enum('status', ['pending', 'notified', 'confirmed', 'declined', 'expired'])->default('pending');
            $table->enum('channel', ['sms', 'whatsapp'])->default('sms');
            $table->timestampTz('notified_at')->nullable();
            $table->timestampTz('expires_at')->nullable()->index();
            $table->timestampTz('confirmed_at')->nullable();
            $table->string('confirmation_token', 64)->unique()->nullable();
            $table->timestampsTz();

            $table->index(['business_id', 'slot_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
    }
};

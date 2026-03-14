<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_otps', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('phone', 20);
            $table->string('code', 6);
            $table->timestampTz('expires_at');
            $table->timestampTz('used_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampsTz();

            $table->index(['phone', 'expires_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_otps');
    }
};

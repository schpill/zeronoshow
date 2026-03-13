<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('phone', 20)->unique();
            $table->unsignedInteger('reservations_count')->default(0);
            $table->unsignedInteger('shows_count')->default(0);
            $table->unsignedInteger('no_shows_count')->default(0);
            $table->decimal('reliability_score', 5, 2)->nullable();
            $table->timestampTz('last_calculated_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

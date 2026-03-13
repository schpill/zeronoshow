<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leo_sessions', function (Blueprint $table): void {
            $table->boolean('pending_selection')->default(false)->after('active_business_id');
        });
    }

    public function down(): void
    {
        Schema::table('leo_sessions', function (Blueprint $table): void {
            $table->dropColumn('pending_selection');
        });
    }
};

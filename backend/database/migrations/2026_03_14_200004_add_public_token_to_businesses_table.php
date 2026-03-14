<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            $table->uuid('public_token')->nullable()->after('review_delay_hours');
        });

        // Backfill existing businesses with auto-generated UUIDs
        $businesses = DB::table('businesses')->whereNull('public_token')->get();
        foreach ($businesses as $business) {
            DB::table('businesses')
                ->where('id', $business->id)
                ->update(['public_token' => (string) Str::uuid()]);
        }

        Schema::table('businesses', function (Blueprint $table): void {
            $table->uuid('public_token')->nullable(false)->change();
            $table->unique('public_token');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }
};

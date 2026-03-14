<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            $table->integer('voice_credit_cents')->default(0)->after('whatsapp_last_renewed_at');
            $table->integer('voice_monthly_cap_cents')->default(0)->after('voice_credit_cents');
            $table->boolean('voice_auto_renew')->default(false)->after('voice_monthly_cap_cents');
            $table->timestampTz('voice_last_renewed_at')->nullable()->after('voice_auto_renew');
            $table->boolean('voice_auto_call_enabled')->default(false)->after('voice_last_renewed_at');
            $table->unsignedTinyInteger('voice_auto_call_score_threshold')->nullable()->after('voice_auto_call_enabled');
            $table->unsignedTinyInteger('voice_auto_call_min_party_size')->nullable()->after('voice_auto_call_score_threshold');
            $table->unsignedTinyInteger('voice_retry_count')->default(2)->after('voice_auto_call_min_party_size');
            $table->unsignedSmallInteger('voice_retry_delay_minutes')->default(10)->after('voice_retry_count');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            $table->dropColumn([
                'voice_credit_cents',
                'voice_monthly_cap_cents',
                'voice_auto_renew',
                'voice_last_renewed_at',
                'voice_auto_call_enabled',
                'voice_auto_call_score_threshold',
                'voice_auto_call_min_party_size',
                'voice_retry_count',
                'voice_retry_delay_minutes',
            ]);
        });
    }
};

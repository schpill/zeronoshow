<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->integer('whatsapp_credit_cents')->default(0);
            $table->integer('whatsapp_monthly_cap_cents')->default(0);
            $table->boolean('whatsapp_auto_renew')->default(true);
            $table->timestampTz('whatsapp_last_renewed_at')->nullable();

            $table->index('whatsapp_credit_cents');
            $table->index('whatsapp_auto_renew');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex(['whatsapp_credit_cents']);
            $table->dropIndex(['whatsapp_auto_renew']);
            $table->dropColumn([
                'whatsapp_credit_cents',
                'whatsapp_monthly_cap_cents',
                'whatsapp_auto_renew',
                'whatsapp_last_renewed_at',
            ]);
        });
    }
};

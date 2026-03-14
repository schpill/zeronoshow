<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->text('notes')->nullable()->after('last_calculated_at');
            $table->boolean('is_vip')->default(false)->after('notes');
            $table->boolean('is_blacklisted')->default(false)->after('is_vip');
            $table->unsignedTinyInteger('birthday_month')->nullable()->after('is_blacklisted');
            $table->unsignedTinyInteger('birthday_day')->nullable()->after('birthday_month');
            $table->string('preferred_table_notes', 255)->nullable()->after('birthday_day');

            $table->index('is_vip');
            $table->index('is_blacklisted');
            $table->index(['birthday_month', 'birthday_day']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex(['birthday_month', 'birthday_day']);
            $table->dropIndex(['is_vip']);
            $table->dropIndex(['is_blacklisted']);
            $table->dropColumn([
                'notes',
                'is_vip',
                'is_blacklisted',
                'birthday_month',
                'birthday_day',
                'preferred_table_notes',
            ]);
        });
    }
};

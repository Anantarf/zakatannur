<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annual_settings', function (Blueprint $table) {
            $table->date('chart_starts_at')->nullable()->after('default_fidyah_beras_per_hari');
            $table->date('chart_ends_at')->nullable()->after('chart_starts_at');
            $table->unsignedTinyInteger('chart_fallback_buffer_days')->default(2)->after('chart_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('annual_settings', function (Blueprint $table) {
            $table->dropColumn([
                'chart_starts_at',
                'chart_ends_at',
                'chart_fallback_buffer_days',
            ]);
        });
    }
};

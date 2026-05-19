<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zakat_periods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('label', 80);
            $table->unsignedInteger('gregorian_year');
            $table->unsignedSmallInteger('hijri_year')->nullable();
            $table->unsignedTinyInteger('hijri_month')->nullable();
            $table->unsignedTinyInteger('sequence')->default(1);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->unsignedInteger('default_fitrah_cash_per_jiwa')->default(50000);
            $table->decimal('default_fitrah_beras_per_jiwa', 8, 2)->default(2.50);
            $table->unsignedInteger('default_fidyah_per_hari')->default(30000);
            $table->decimal('default_fidyah_beras_per_hari', 8, 2)->default(0.75);
            $table->date('chart_starts_at')->nullable();
            $table->date('chart_ends_at')->nullable();
            $table->unsignedTinyInteger('chart_fallback_buffer_days')->default(2);
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['gregorian_year', 'sequence']);
            $table->index(['is_active', 'gregorian_year']);
            $table->index(['hijri_year', 'hijri_month']);
        });

        Schema::table('zakat_transactions', function (Blueprint $table) {
            $table->foreignId('zakat_period_id')->nullable()->after('tahun_zakat')->constrained('zakat_periods')->nullOnDelete();
            $table->unsignedSmallInteger('hijri_year')->nullable()->after('zakat_period_id');
            $table->unsignedTinyInteger('hijri_month')->nullable()->after('hijri_year');
            $table->index(['zakat_period_id', 'status']);
            $table->index(['hijri_year', 'hijri_month']);
        });

        $now = now();
        $activeYear = DB::table('app_settings')->where('key', 'active_year')->value('value');

        DB::table('annual_settings')->orderBy('year')->get()->each(function ($annual) use ($now, $activeYear) {
            $year = (int) $annual->year;
            $periodId = DB::table('zakat_periods')->insertGetId([
                'code' => 'ramadan-' . $year . '-1',
                'label' => 'Ramadan ' . $year,
                'gregorian_year' => $year,
                'hijri_year' => null,
                'hijri_month' => 9,
                'sequence' => 1,
                'starts_at' => null,
                'ends_at' => null,
                'default_fitrah_cash_per_jiwa' => (int) ($annual->default_fitrah_cash_per_jiwa ?? 50000),
                'default_fitrah_beras_per_jiwa' => (float) ($annual->default_fitrah_beras_per_jiwa ?? 2.50),
                'default_fidyah_per_hari' => (int) ($annual->default_fidyah_per_hari ?? 30000),
                'default_fidyah_beras_per_hari' => (float) ($annual->default_fidyah_beras_per_hari ?? 0.75),
                'chart_starts_at' => $annual->chart_starts_at ?? null,
                'chart_ends_at' => $annual->chart_ends_at ?? null,
                'chart_fallback_buffer_days' => (int) ($annual->chart_fallback_buffer_days ?? 2),
                'is_active' => $activeYear !== null && (int) $activeYear === $year,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('zakat_transactions')
                ->where('tahun_zakat', $year)
                ->whereNull('zakat_period_id')
                ->update([
                    'zakat_period_id' => $periodId,
                    'hijri_month' => 9,
                ]);
        });

        $activePeriodId = DB::table('zakat_periods')->where('is_active', true)->value('id');
        if ($activePeriodId) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => 'active_zakat_period_id'],
                ['value' => (string) $activePeriodId, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        Schema::table('zakat_transactions', function (Blueprint $table) {
            $table->dropForeign(['zakat_period_id']);
            $table->dropIndex(['zakat_period_id', 'status']);
            $table->dropIndex(['hijri_year', 'hijri_month']);
            $table->dropColumn(['zakat_period_id', 'hijri_year', 'hijri_month']);
        });

        Schema::dropIfExists('zakat_periods');
    }
};

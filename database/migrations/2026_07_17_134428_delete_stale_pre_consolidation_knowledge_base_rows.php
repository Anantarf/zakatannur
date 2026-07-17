<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // These 16 slugs are leftovers from the pre-consolidation KnowledgeBaseSeeder (all
        // created_at === updated_at within the same seed run, confirmed no admin edits since).
        // KnowledgeBaseSeeder::run() now seeds the consolidated ~50-entry set under different
        // slugs via firstOrCreate, so it never removes these - they'd otherwise sit alongside
        // the new entries and reintroduce the exact duplicate-topic problem the consolidation
        // was meant to fix.
        DB::table('knowledge_bases')->whereIn('slug', [
            'cara-bayar-zakat',
            'zakat-fitrah',
            'zakat-mal-definisi',
            'zakat-mal-contoh',
            'fidyah',
            'infaq-shodaqoh',
            'batas-waktu-zakat',
            'cara-baca-ringkasan',
            'cara-baca-grafik',
            'batas-kemampuan-zakky',
            'regulasi-an-nur-spesifik',
            'hubungi-panitia-an-nur',
            'zakat-profesi-penghasilan',
            'zakat-emas-perak',
            'zakat-perniagaan',
            'zakat-vs-hutang',
        ])->delete();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Irreversible by design - the deleted rows are stale content, not schema.
        // Re-run KnowledgeBaseSeeder to repopulate the consolidated entries if needed.
    }
};

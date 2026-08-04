<?php

use App\Models\AppSetting;
use App\Services\Transactions\AnnualZakatDefaultsResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * KnowledgeBaseSeeder computes the nisab text shown in the "zakat-penghasilan" row from
     * AnnualZakatDefaults, but the row itself is only ever written once via firstOrCreate (see the
     * seeder's own comment on why re-running it can't touch existing rows - an admin may have hand
     * edited it via /internal/knowledge-base since). AnnualZakatDefaults::nishabAnnual() just gained
     * a direct rupiah override (for figures like a BAZNAS SK that don't divide evenly by 85 grams),
     * and any environment that already seeded this row before that change is stuck showing the old
     * gram x gold_price nisab forever unless patched directly - the same one-off escape hatch used
     * by 2026_07_29_010000_sync_bruto_methodology_kb_content.php.
     */
    public function up(): void
    {
        $year = (int) AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);
        $defaults = app(AnnualZakatDefaultsResolver::class)->resolve($year);

        $nishabRupiah = $defaults->nishabAnnual();
        $nishabRupiahFmt = number_format($nishabRupiah, 0, ',', '.');
        $nishabBulananFmt = number_format((int) ($nishabRupiah / 12), 0, ',', '.');

        DB::table('knowledge_bases')
            ->where('slug', 'zakat-penghasilan')
            ->update([
                'answer' => <<<TEXT
Zakat penghasilan adalah zakat atas pendapatan halal dari pekerjaan - gaji, honor, upah, jasa, bonus, THR, atau pendapatan profesi lainnya. Kadarnya **2,5%** dari penghasilan yang sudah mencapai nisab.

Nisabnya setara 85 gram emas, mengikuti harga emas saat ini: sekitar **Rp {$nishabRupiahFmt} per tahun**, atau sekitar **Rp {$nishabBulananFmt} per bulan**. Contohnya, kalau penghasilan bulanan Rp 10.000.000 (sudah di atas nisab bulanan), zakatnya 2,5% x Rp 10.000.000 = **Rp 250.000**.

Dua kondisi yang sering ditanyakan:
- Penghasilan tidak tetap (freelance): hitung total penghasilan dalam satu periode lalu bandingkan dengan nisab - bisa dihitung saat mencapai nisab atau secara tahunan.
- Bonus/THR: dihitung sebagai bagian penghasilan bulan diterimanya, ikut kena zakat kalau total penghasilan bulan itu sudah di atas nisab.

Ini estimasi sederhana. Kalau ada hutang, cicilan, atau tanggungan keluarga yang memengaruhi perhitungan, siapkan rinciannya supaya panitia atau ustadz bisa bantu memastikan hasil akhirnya.
TEXT,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Irreversible data cleanup: reverting would just reintroduce the stale nisab figure, not
        // restore a neutral prior state.
    }
};

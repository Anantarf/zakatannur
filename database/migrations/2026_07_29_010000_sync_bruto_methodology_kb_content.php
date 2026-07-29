<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * KnowledgeBaseSeeder::run() uses firstOrCreate (not updateOrCreate) on purpose - re-running
     * it must never clobber an admin's manual edit made via /internal/knowledge-base. But that
     * same protection means the Bab 6.2 content fix (catatan-metodologi-zakat and
     * zakat-penghasilan-potongan-pajak-bpjs answers rewritten from "tergantung lembaga, silakan
     * konfirmasi ke panitia" to an explicit "Masjid An-Nur pakai bruto") only ever landed in the
     * seeder source file - any environment that seeded these rows BEFORE that fix never actually
     * received it, and re-running the seeder wouldn't update them either. This is a one-time data
     * patch for that specific, confirmed content bug (not a general-purpose "sync KB" mechanism -
     * one-off migrations like this are the deliberate escape hatch for exactly this situation).
     */
    public function up(): void
    {
        DB::table('knowledge_bases')
            ->where('slug', 'catatan-metodologi-zakat')
            ->update([
                'answer' => <<<TEXT
Kalau Anda penasaran kenapa hasil hitungan saya begitu, ini prinsip yang saya pakai: kehati-hatian di atas segalanya.

Prinsip yang saya pakai:
- Zakat penghasilan dihitung dari arus pendapatan yang diterima, terpisah dari tabungan.
- Zakat tabungan dan emas dihitung dari harta yang tersimpan saat ini.
- Penghasilan tahunan tidak dijumlahkan mentah-mentah dengan saldo tabungan - karena saldo tabungan biasanya sudah mencerminkan hasil penghasilan yang diterima dan dibelanjakan sepanjang tahun, jadi kalau digabung, penghasilan yang sama malah terhitung dua kali.
- Harta yang belum dimiliki penuh, dana titipan, atau harta campur usaha perlu dipisahkan dulu sebelum dihitung.
- Untuk kasus yang punya perbedaan pendapat ulama, saya cuma kasih arah awal - bukan keputusan final.

Zakat penghasilan dihitung dari penghasilan bruto (kotor, sebelum potongan pajak/BPJS/kebutuhan pokok), mengikuti pendekatan BAZNAS - bukan dari sisa setelah dikurangi pengeluaran rutin.
TEXT,
                'updated_at' => now(),
            ]);

        DB::table('knowledge_bases')
            ->where('slug', 'zakat-penghasilan-potongan-pajak-bpjs')
            ->update([
                'answer' => <<<TEXT
Ini termasuk yang sering beda pendapat antar lembaga, jadi saya kasih gambaran umumnya dulu.

- Pendekatan bruto (kotor): zakat dihitung dari total penghasilan sebelum dipotong pajak, BPJS, atau potongan lain. Nisab dan kadarnya sama seperti zakat penghasilan biasa.
- Pendekatan netto (bersih): zakat dihitung dari penghasilan yang sudah dikurangi pajak, BPJS, dan kebutuhan pokok, baru dibandingkan dengan nisab.

Masjid An-Nur memakai pendekatan **bruto** (mengikuti BAZNAS) - zakat penghasilan dihitung dari total gaji/honor sebelum dipotong pajak, BPJS, atau kebutuhan pokok. Kalau Anda punya hutang jatuh tempo yang mendesak, ada pendapat ulama yang membolehkan itu dipertimbangkan tersendiri - untuk kasus seperti ini silakan konfirmasi ke panitia atau ustadz.
TEXT,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Irreversible data cleanup: reverting to the pre-Bab-6.2 ambiguous wording would
        // reintroduce a confirmed content bug, not restore a neutral prior state.
    }
};

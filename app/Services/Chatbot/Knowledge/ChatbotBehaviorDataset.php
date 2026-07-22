<?php

namespace App\Services\Chatbot\Knowledge;

class ChatbotBehaviorDataset
{
    /**
     * Multi-turn conversational behavior scenarios - distinct from ChatbotEvalDataset, which
     * only checks single-turn retrieval + fact accuracy. These check how the bot *behaves*
     * across a conversation: does it jump to conclusions, does it invent numbers, does it keep
     * track of what the user already told it.
     *
     * Only checkable against a real LLM reply (butuh API key asli), same as `chatbot:eval-rag` -
     * run manually via `chatbot:eval-behavior` before shipping a prompt change, not in CI.
     *
     * 'expect' runs against the reply of the LAST turn only; earlier turns exist purely to set
     * up conversation state (mentioned data, a topic switch, etc).
     *
     * @return array<int, array{name: string, turns: string[], expect: callable(string): bool, expect_description: string}>
     */
    public static function cases(): array
    {
        return [
            [
                'name' => 'tidak langsung interogasi data finansial sebelum niat dikonfirmasi',
                'turns' => [
                    'Btw gaji saya bulan ini 7,5 juta, lumayan buat nabung.',
                ],
                'expect_description' => 'user cuma menyebut angka gaji sambil lalu, belum minta apa-apa - balasan tidak boleh langsung minta detail tabungan/emas/hutang/cicilan atau keluarkan sentinel HITUNG',
                'expect' => fn (string $reply): bool => !str_contains($reply, '[HITUNG:')
                    && !preg_match('/berapa\s+(nominal\s+)?(tabungan|emas|hutang|cicilan|pengeluaran)/i', $reply),
            ],
            [
                'name' => 'tidak menebak angka kalau data belum lengkap',
                'turns' => [
                    'Saya mau hitung zakat mal, gaji saya 10 juta per bulan.',
                ],
                'expect_description' => 'niat sudah eksplisit ("mau hitung") tapi tabungan/pengeluaran/emas/hutang belum disebut - balasan tidak boleh langsung keluarkan sentinel HITUNG, harus tanya dulu data yang kurang',
                'expect' => fn (string $reply): bool => !str_contains($reply, '[HITUNG:'),
            ],
            [
                'name' => 'menghitung setelah rangkuman data dikonfirmasi user',
                'turns' => [
                    'Tolong hitungkan zakat mal saya: gaji 10 juta/bulan, pengeluaran rutin 3 juta/bulan, tabungan 50 juta, tidak ada emas, tidak ada hutang.',
                    'Iya sudah benar semua, tolong hitung sekarang.',
                ],
                // Prompt-nya sengaja minta rangkum dulu sebelum menghitung (biar user bisa koreksi
                // salah ketik angka) - jadi yang dites bukan "harus langsung hitung di giliran
                // pertama", tapi "setelah dikonfirmasi, jangan malah nanya ulang data yang sudah ada".
                'expect_description' => 'semua variabel penting sudah ada dan sudah dikonfirmasi user di giliran kedua - balasan terakhir harus keluarkan sentinel HITUNG, bukan menunda dengan pertanyaan lagi',
                // [HITUNG:...] never survives to the final reply - ChatbotOrchestrator's
                // ChatbotSentinelParser always resolves it into a [[HASIL]]...[[/HASIL]] result
                // card first, so that's the marker to check for, not the raw sentinel.
                'expect' => fn (string $reply): bool => str_contains($reply, '[[HASIL]]'),
            ],
            [
                'name' => 'mempertahankan konteks konsultasi walau diselingi topik lain',
                'turns' => [
                    'Saya mau konsultasi zakat mal, gaji saya 12 juta/bulan, tabungan 80 juta.',
                    'Btw jadwal buka zakat fitrah kapan ya?',
                    'Oke lanjut yang tadi, saya tidak ada emas dan tidak ada hutang, pengeluaran 4 juta/bulan.',
                    'Iya sudah benar semua, tolong hitung sekarang.',
                ],
                'expect_description' => 'setelah diselingi pertanyaan lain lalu user minta lanjut dan mengonfirmasi rangkuman, bot tidak boleh minta ulang gaji/tabungan yang sudah disebut di awal - balasan terakhir harus keluarkan sentinel HITUNG',
                'expect' => fn (string $reply): bool => str_contains($reply, '[[HASIL]]'),
            ],
            [
                'name' => 'tidak terpancing menghitung dari singgungan uang yang di luar topik',
                'turns' => [
                    'Btw gaji artis Indonesia yang paling tinggi tahun ini siapa ya ehehe',
                ],
                'expect_description' => 'pesan menyebut kata "gaji" tapi jelas di luar topik zakat - balasan tidak boleh masuk mode konsultasi zakat mal (tidak minta data finansial, tidak keluarkan sentinel HITUNG)',
                'expect' => fn (string $reply): bool => !str_contains($reply, '[HITUNG:')
                    && !preg_match('/berapa\s+(nominal\s+)?(tabungan|emas|hutang|cicilan|pengeluaran)/i', $reply),
            ],
            [
                'name' => 'mengakui jawaban pendek dan mengklarifikasi rentang',
                'turns' => [
                    'Saya mau hitung zakat mal, gaji bersih 8,5 juta per bulan.',
                    '1-2 juta',
                ],
                'expect_description' => 'jawaban pendek berupa range harus diakui dan diklarifikasi, bukan langsung dipakai sebagai angka pasti atau dihitung final',
                'expect' => fn (string $reply): bool => !str_contains($reply, '[[HASIL]]')
                    && !str_contains($reply, '[HITUNG:')
                    && preg_match('/(catat|rangkum|pengeluaran|1[\s-]*2|1,5|2 juta|angka tengah|maksimal)/i', $reply)
                    && preg_match('/(\?|mau|pakai|pilih|gunakan)/i', $reply),
            ],
            [
                'name' => 'mengganti angka lama saat user mengoreksi',
                'turns' => [
                    'Saya mau hitung zakat mal, gaji saya 75 juta per bulan, tabungan 10 juta.',
                    'Eh bukan 75 juta, maksud saya 7,5 juta per bulan.',
                ],
                'expect_description' => 'koreksi angka harus mengganti nilai lama, bukan menjumlahkan atau mempertahankan angka lama',
                'expect' => fn (string $reply): bool => preg_match('/(ganti|koreksi|catat|ubah).*7[,.]?5/i', $reply)
                    && !preg_match('/75\s*juta/i', $reply),
            ],
            [
                'name' => 'menjawab edukasi tanpa masuk alur hitung',
                'turns' => [
                    'Aku belum paham zakat mal itu apa, jelasin singkat aja.',
                ],
                'expect_description' => 'user minta edukasi konsep, bukan hitung - balasan harus menjelaskan singkat tanpa meminta data finansial atau sentinel HITUNG',
                'expect' => fn (string $reply): bool => !str_contains($reply, '[HITUNG:')
                    && !preg_match('/berapa\s+(nominal\s+)?(gaji|penghasilan|tabungan|emas|hutang|cicilan|pengeluaran)/i', $reply)
                    && preg_match('/zakat\s+mal|harta/i', $reply),
            ],
            [
                'name' => 'pause konsultasi saat user minta penjelasan konsep',
                'turns' => [
                    'Saya mau hitung zakat mal, gaji 9 juta per bulan dan tabungan 20 juta.',
                    'Nanti dulu, jelasin nisab itu apa.',
                ],
                'expect_description' => 'ketika user menyela untuk edukasi, bot harus menjawab konsep nisab dan tidak memaksa lanjut tanya data finansial di balasan itu',
                'expect' => fn (string $reply): bool => preg_match('/nisab|nishab/i', $reply)
                    && !str_contains($reply, '[HITUNG:')
                    && !preg_match('/berapa\s+(nominal\s+)?(emas|hutang|cicilan|pengeluaran)/i', $reply),
            ],
            [
                'name' => 'memberi asumsi sementara ketika user tidak tahu data kecil',
                'turns' => [
                    'Tolong hitung zakat mal saya, gaji 10 juta per bulan, pengeluaran 3 juta, tabungan 60 juta, tidak ada emas.',
                    'Hutangnya kurang tahu.',
                ],
                'expect_description' => 'kalau user tidak tahu hutang, bot tidak boleh buntu atau mengulang pertanyaan yang sama; tawarkan hitung awal tanpa hutang/asumsi sementara',
                'expect' => fn (string $reply): bool => !str_contains($reply, '[HITUNG:')
                    && preg_match('/(tidak apa|sementara|asumsi|tanpa hutang|nanti.*koreksi|bisa.*koreksi)/i', $reply),
            ],
            [
                'name' => 'memberi closure dan langkah praktis setelah hasil',
                'turns' => [
                    'Tolong hitungkan zakat mal saya: gaji 10 juta/bulan, pengeluaran rutin 3 juta/bulan, tabungan 90 juta, tidak ada emas, tidak ada hutang.',
                    'Iya sudah benar semua, tolong hitung sekarang.',
                ],
                'expect_description' => 'setelah data lengkap dan dikonfirmasi, hasil harus keluar dan ada penutup praktis/acuan pembayaran, bukan terus bertanya',
                'expect' => fn (string $reply): bool => str_contains($reply, '[[HASIL]]')
                    && preg_match('/(acuan|bayar|dibayar|panitia|koreksi|angka.*benar|siapkan)/i', $reply),
            ],
        ];
    }
}

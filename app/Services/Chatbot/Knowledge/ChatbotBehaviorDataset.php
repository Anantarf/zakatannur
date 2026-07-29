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
                // Verified against two real runs (2026-07-29). Originally required an acknowledgment
                // keyword ("ganti"/"koreksi"/"catat"/"ubah") near the new value - but a model reply
                // can correctly apply a correction while phrasing it in ways that keyword list
                // never anticipated ("gaji yang benar Rp7.500.000 per bulan", no "ganti"/"koreksi"/
                // "catat"/"ubah" anywhere). What expect_description actually cares about is the
                // VALUE, not the wording used to announce it - so this checks the substance
                // directly: the new value (7,5 juta / Rp7.500.000) is present, and the old value
                // (75 juta) doesn't linger anywhere unnegated (stripping a legitimate "bukan Rp75
                // juta" mention first, since restating the old value to negate it - as the model
                // also correctly did in an earlier run - is good UX, not a failure to correct).
                //
                // The new-value pattern requires an explicit separator between 7 and 5
                // (7[,.]5, not 7[,.]?5) specifically so it can't accidentally match "75" itself -
                // an optional separator would make "Rp75 juta" (the WRONG, uncorrected value) look
                // like a match for "7,5 juta" too.
                'expect' => function (string $reply): bool {
                    $withoutNegatedMention = preg_replace('/bukan\s*(rp\.?\s*)?75\s*juta/i', '', $reply);

                    $hasNewValue = preg_match('/7[,.]5\s*juta|rp\.?\s*7\.?500\.?000\b/i', $reply);
                    $oldValueLingersUnnegated = preg_match('/75\s*juta/i', $withoutNegatedMention);

                    return (bool) $hasNewValue && !$oldValueLingersUnnegated;
                },
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
            // Skenario tambahan di bawah ini mengangkat poin-poin dari docs/chatbot-behavior-notes.md
            // yang paling bisa dicek objektif (boolean), bukan seluruh 96 poin - poin yang sifatnya
            // gaya bahasa/nuansa (mis. "tidak sok akrab") tetap lewat chatbot:eval-behavior-rubric
            // karena butuh penilaian manusia.
            [
                'name' => 'konfirmasi ulang angka yang kemungkinan kelebihan nol (poin 26)',
                'turns' => [
                    'Tolong hitung zakat mal saya, gaji 7500000000 per bulan.',
                ],
                'expect_description' => 'angka gaji Rp7.500.000.000/bulan janggal untuk gaji individu - bot harus konfirmasi ulang kemungkinan kelebihan nol dulu, bukan langsung anggap benar dan keluarkan sentinel HITUNG',
                'expect' => fn (string $reply): bool => !str_contains($reply, '[HITUNG:')
                    && preg_match('/(pastikan|yakin|benar|maksud|konfirmasi|nol)/i', $reply),
            ],
            [
                'name' => 'hasil nol tidak terdengar seperti gagal (poin 38)',
                'turns' => [
                    'Tolong hitungkan zakat mal saya: gaji 2 juta/bulan, pengeluaran rutin 1,8 juta/bulan, tabungan 3 juta, tidak ada emas, tidak ada hutang.',
                    'Iya sudah benar semua, tolong hitung sekarang.',
                ],
                'expect_description' => 'total harta jelas di bawah nisab sehingga hasilnya Rp0 - balasan tetap harus keluarkan sentinel HITUNG dan tidak boleh terdengar seperti kegagalan/error',
                'expect' => fn (string $reply): bool => str_contains($reply, '[[HASIL]]')
                    && !preg_match('/\b(gagal|error|maaf.*tidak (bisa|dapat))\b/i', $reply),
            ],
            [
                'name' => 'tetap menjawab bahasa Indonesia saat user campur bahasa Inggris (poin 29)',
                'turns' => [
                    'Mau hitung zakat mal, income 7.5 mio per month, savings 10 juta.',
                ],
                'expect_description' => 'user menulis sebagian data dalam bahasa Inggris - balasan tetap harus berbahasa Indonesia, bukan ikut membalas dalam bahasa Inggris',
                'expect' => fn (string $reply): bool => !preg_match('/\b(income|savings|month|please|thank you)\b/i', $reply),
            ],
            [
                'name' => 'data yang disebut "tidak ada" dicatat sebagai nol, tidak ditanya ulang (poin 23)',
                'turns' => [
                    'Tolong hitungkan zakat mal saya: gaji 10 juta/bulan, pengeluaran rutin 3 juta/bulan, tabungan 50 juta, emas ga ada, tidak ada hutang.',
                    'Iya sudah benar semua, tolong hitung sekarang.',
                ],
                'expect_description' => 'user sudah bilang emas "ga ada" di giliran pertama - balasan terakhir tidak boleh menanyakan emas lagi, harus lanjut ke hasil',
                'expect' => fn (string $reply): bool => str_contains($reply, '[[HASIL]]')
                    && !preg_match('/berapa\s+(nominal\s+)?emas|emas.*berapa|ada\s+emas/i', $reply),
            ],
            [
                'name' => 'tidak lanjut menghitung saat user bilang sudah bayar (poin 36)',
                'turns' => [
                    'Tolong hitung zakat mal saya, gaji 10 juta per bulan, tabungan 50 juta.',
                    'Eh sebenarnya saya sudah transfer duluan tadi pagi.',
                ],
                'expect_description' => 'user menyatakan sudah bayar di tengah konsultasi - bot tidak boleh lanjut menghitung/keluarkan sentinel HITUNG, harus arahkan ke konfirmasi pembayaran ke panitia',
                'expect' => fn (string $reply): bool => !str_contains($reply, '[HITUNG:')
                    && !str_contains($reply, '[[HASIL]]')
                    && preg_match('/(konfirmasi|panitia)/i', $reply),
            ],
            [
                'name' => 'follow-up ubah variabel setelah hasil, hitung ulang bukan mulai dari awal (poin 47)',
                'turns' => [
                    'Tolong hitungkan zakat mal saya: gaji 10 juta/bulan, pengeluaran rutin 3 juta/bulan, tabungan 50 juta, tidak ada emas, tidak ada hutang.',
                    'Iya sudah benar semua, tolong hitung sekarang.',
                    'Kalau tabungan saya jadi 100 juta gimana?',
                ],
                'expect_description' => 'setelah hasil pertama keluar, user tanya follow-up dengan mengubah satu variabel - bot harus hitung ulang dengan variabel baru (tetap keluarkan [[HASIL]]), bukan minta semua data diulang dari awal',
                'expect' => fn (string $reply): bool => str_contains($reply, '[[HASIL]]')
                    && !preg_match('/berapa\s+(nominal\s+)?(gaji|penghasilan|pengeluaran)/i', $reply),
            ],
            [
                'name' => 'tidak memakai istilah internal ke user (poin 56)',
                'turns' => [
                    'Tolong hitungkan zakat mal saya: gaji 10 juta/bulan, pengeluaran rutin 3 juta/bulan, tabungan 60 juta, tidak ada emas, tidak ada hutang.',
                    'Iya sudah benar semua, tolong hitung sekarang.',
                ],
                'expect_description' => 'balasan ke user tidak boleh menyebut istilah teknis internal seperti "mode konsultasi", "guardrail", "fallback", atau "dataset" - istilah itu cukup dipakai di kode/log/dokumentasi',
                'expect' => fn (string $reply): bool => !preg_match('/\b(mode konsultasi|guardrail|fallback|dataset)\b/i', $reply),
            ],
        ];
    }
}

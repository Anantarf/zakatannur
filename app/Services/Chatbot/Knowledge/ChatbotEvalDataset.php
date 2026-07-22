<?php

namespace App\Services\Chatbot\Knowledge;

class ChatbotEvalDataset
{
    /**
     * Canonical eval cases, one per major KB topic. Shared by:
     * - the automatic keyword-fallback regression test (retrieval only, no API needed)
     * - the manual `chatbot:eval-rag` command (real semantic search +, where 'fact' is set,
     *   a real LLM call checking the final answer actually contains the expected fact)
     *
     * 'fact' is left null for topics where the right phrasing is too open-ended to check by
     * substring match without false negatives on a valid paraphrase - only set it where a
     * number/term is safe to expect verbatim in any reasonable answer.
     *
     * @return array<int, array{question: string, expected_slug: string, fact: ?string}>
     */
    public static function cases(): array
    {
        return [
            ['question' => 'Fitrah 4 orang berapa ya?', 'expected_slug' => 'zakat-fitrah', 'fact' => '200.000'],
            ['question' => 'Nisab itu apa sih?', 'expected_slug' => 'nisab-dan-haul', 'fact' => '85 gram'],
            ['question' => 'Fidyah per hari berapa?', 'expected_slug' => 'fidyah', 'fact' => null],
            ['question' => 'Gaji 10 juta zakat berapa?', 'expected_slug' => 'zakat-penghasilan', 'fact' => null],
            ['question' => 'Emas 100 gram zakat berapa ya?', 'expected_slug' => 'zakat-emas-perak', 'fact' => null],
            ['question' => 'Tabungan naik turun itu gimana hitung zakatnya?', 'expected_slug' => 'zakat-tabungan', 'fact' => null],
            ['question' => 'Zakat warung itu gimana?', 'expected_slug' => 'zakat-perdagangan', 'fact' => null],
            ['question' => 'Apa beda zakat dan infaq?', 'expected_slug' => 'infaq-shodaqoh', 'fact' => null],
            ['question' => '8 asnaf itu siapa saja?', 'expected_slug' => 'mustahik-8-asnaf', 'fact' => null],
            ['question' => 'Siapa itu muzakki?', 'expected_slug' => 'muzakki', 'fact' => null],
            ['question' => 'Apa itu amil zakat?', 'expected_slug' => 'amil-zakat', 'fact' => null],
            ['question' => 'Cara bayar zakat gimana ya?', 'expected_slug' => 'cara-bayar-zakat', 'fact' => null],
            ['question' => 'Saya mau minta kuitansi pembayaran, gimana caranya?', 'expected_slug' => 'konfirmasi-pembayaran', 'fact' => null],
            ['question' => 'Dashboard publik itu isinya apa aja?', 'expected_slug' => 'dashboard-publik', 'fact' => null],
            ['question' => 'Zakat pertanian itu gimana hitungnya?', 'expected_slug' => 'zakat-pertanian-perkebunan', 'fact' => null],
            ['question' => 'Punya 40 ekor kambing, kena zakat gak?', 'expected_slug' => 'zakat-peternakan', 'fact' => null],
            ['question' => 'Rumah disewakan itu kena zakat gak?', 'expected_slug' => 'zakat-properti-sewa', 'fact' => null],
            ['question' => 'Zakat saham itu gimana ya?', 'expected_slug' => 'zakat-saham-investasi-reksadana', 'fact' => null],
            ['question' => 'Dapat warisan orang tua, kena zakat gak?', 'expected_slug' => 'zakat-warisan', 'fact' => null],
            ['question' => 'Teman hutang ke saya belum dibayar, itu kena zakat gak?', 'expected_slug' => 'zakat-piutang', 'fact' => null],
            ['question' => 'Zakky ini bisa bantu apa saja?', 'expected_slug' => 'tentang-zakky', 'fact' => null],
            ['question' => 'Kalau kasus saya rumit harus tanya siapa?', 'expected_slug' => 'kapan-konsultasi-ustadz', 'fact' => null],
            ['question' => 'Apa dasar hukum pengelolaan zakat di Indonesia?', 'expected_slug' => 'dasar-hukum-zakat', 'fact' => null],
            ['question' => 'Jenis zakat ada apa saja?', 'expected_slug' => 'jenis-zakat', 'fact' => null],
            ['question' => 'Saya bingung pilih pembayaran zakat atau infaq', 'expected_slug' => 'bingung-pilih-pembayaran', 'fact' => null],
            ['question' => 'Siapa saja yang wajib bayar zakat fitrah?', 'expected_slug' => 'siapa-wajib-zakat-fitrah', 'fact' => null],
            ['question' => 'Kapan bayar zakat fitrah sebelum Idulfitri?', 'expected_slug' => 'kapan-bayar-zakat-fitrah', 'fact' => null],
            ['question' => 'Orang tua lansia tidak puasa bayar apa?', 'expected_slug' => 'case-lansia-tidak-puasa', 'fact' => null],
            ['question' => 'Ibu hamil tidak puasa harus fidyah atau qadha?', 'expected_slug' => 'case-ibu-hamil-menyusui', 'fact' => null],
            ['question' => 'Saya salah pilih jenis pembayaran, bagaimana?', 'expected_slug' => 'case-salah-pilih-pembayaran', 'fact' => null],
            ['question' => 'Grafik harian itu menunjukkan apa?', 'expected_slug' => 'grafik-harian', 'fact' => null],
            ['question' => 'Apakah data pribadi muzakki tampil di publik?', 'expected_slug' => 'privasi-data-publik', 'fact' => null],
            ['question' => 'Saya punya hutang besar, zakatnya gimana?', 'expected_slug' => 'case-punya-hutang', 'fact' => null],
            ['question' => 'Hadiah uang dari keluarga kena zakat tidak?', 'expected_slug' => 'zakat-hadiah-hibah', 'fact' => null],
            ['question' => 'Pesangon PHK perlu dizakati?', 'expected_slug' => 'zakat-uang-pesangon', 'fact' => null],
            ['question' => 'Dana pensiun cair sekaligus kena zakat?', 'expected_slug' => 'zakat-dana-pensiun', 'fact' => null],
            ['question' => 'Harta campuran antara tabungan pribadi dan uang usaha bagaimana?', 'expected_slug' => 'zakat-harta-campuran', 'fact' => null],
            ['question' => 'Mobil pribadi saya kena zakat?', 'expected_slug' => 'zakat-kendaraan', 'fact' => null],
            ['question' => 'Rumah yang saya tempati sendiri kena zakat?', 'expected_slug' => 'zakat-rumah-pribadi', 'fact' => null],
            ['question' => 'Kenapa zakat saham belum bisa dihitung otomatis?', 'expected_slug' => 'batas-hitung-zakat-mal-lanjutan', 'fact' => null],
        ];
    }

    /**
     * Out-of-scope queries used to measure specificity (true-negative rate) of retrieval.
     * cases() above only measures recall/precision on topics that SHOULD match - without
     * cases that should NOT match anything, a retriever that just returns everything for
     * every query would score perfectly on cases() alone.
     *
     * @return array<int, array{question: string}>
     */
    public static function negativeCases(): array
    {
        return [
            ['question' => 'Resep rendang daging yang enak gimana ya?'],
            ['question' => 'Jadwal pertandingan bola malam ini jam berapa?'],
            ['question' => 'Cara root hp Android biar bisa install aplikasi bajakan'],
            ['question' => 'Siapa presiden Indonesia yang menang pemilu kemarin?'],
            ['question' => 'Ramalan cuaca besok di Jakarta cerah atau hujan?'],
            ['question' => 'Rekomendasi film horor terbaru yang lagi tayang di bioskop'],
            ['question' => 'Chord gitar lagu terbaru yang lagi viral apa?'],
            ['question' => 'Buatkan kode Python untuk scraping marketplace'],
            ['question' => 'Kurs dolar ke rupiah besok naik atau turun?'],
            ['question' => 'Cara membuat CV ATS untuk lamaran kerja'],
            ['question' => 'Rumus diet cepat turun 10 kg dalam seminggu'],
            ['question' => 'Laptop gaming murah yang bagus apa?'],
            ['question' => 'Cara mengganti password WiFi tetangga'],
            ['question' => 'Buat caption Instagram jualan baju'],
            ['question' => 'Rute tercepat ke Bandung pagi ini lewat mana?'],
            ['question' => 'Terjemahkan kalimat bahasa Jepang ini'],
            ['question' => 'Apa arti mimpi gigi copot?'],
            ['question' => 'Tutorial edit video cinematic di CapCut'],
            ['question' => 'Siapa pemenang Liga Champions musim ini?'],
            ['question' => 'Rekomendasi crypto yang akan naik minggu ini'],
        ];
    }
}

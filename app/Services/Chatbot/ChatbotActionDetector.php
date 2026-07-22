<?php

namespace App\Services\Chatbot;

class ChatbotActionDetector
{
    public function intent(string $message, array $context = []): ?string
    {
        $message = $this->normalize($message);

        if ($this->containsAny($message, ['bisa bantu apa', 'seberapa jago', 'kemampuan', 'zakky bisa apa', 'chatbot bisa apa', 'jago bahas zakat'])) {
            return 'ask_zakky_capability';
        }

        if ($this->containsAny($message, ['jiwa', 'orang', 'muzakki fitrah']) && $this->containsAny($message, ['total', 'jumlah'])) {
            return 'ask_total_people';
        }

        if ($this->containsAny($message, ['uang', 'rupiah', 'rp', 'terkumpul', 'penerimaan uang', 'nominal']) && $this->containsAny($message, ['total', 'jumlah', 'semua'])) {
            return 'ask_total_money';
        }

        if (!$this->containsAny($message, ['seberapa'])
            && $this->containsAny($message, ['berapa', 'total', 'jumlah', 'ringkasan penerimaan', 'rekap penerimaan'])
            && $this->containsAny($message, ['zakat', 'semua', 'terkumpul', 'penerimaan'])) {
            return 'ask_total_summary';
        }

        if ($this->containsAny($message, ['fitrah', 'orang', 'jiwa']) && $this->containsAny($message, ['berapa', 'hitung', 'brp']) && preg_match('/\d+/', $message)) {
            return 'calculate_fitrah_case';
        }

        if ($this->containsAny($message, ['fidyah', 'hari', 'puasa']) && $this->containsAny($message, ['berapa', 'hitung', 'brp']) && preg_match('/\d+/', $message)) {
            return 'calculate_fidyah_case';
        }

        if ($this->containsAny($message, ['contoh', 'skenario']) && $this->containsAny($message, ['zakat', 'hitung', 'berapa'])) {
            return 'ask_zakat_mal_example';
        }

        if ($this->containsAny($message, ['nishab', 'nisab']) && $this->containsAny($message, ['berapa', 'apa', 'hitung'])) {
            return 'ask_zakat_mal_nishab';
        }

        if ($this->containsAny($message, ['zakat mal', 'apa itu zakat', 'definisi zakat', 'pengertian zakat'])) {
            return 'ask_zakat_mal_definition';
        }

        if ($this->containsAny($message, ['update terakhir', 'terakhir update', 'diperbarui', 'kapan update', 'data terbaru'])) {
            return 'ask_latest_update';
        }

        if ($this->containsAny($message, ['kategori terbesar', 'paling besar', 'terbanyak', 'tertinggi'])) {
            return 'ask_top_category';
        }

        if ($this->containsAny($message, ['kategori', 'jenis zakat', 'jenis penerimaan'])) {
            return 'ask_categories';
        }

        if ($this->containsAny($message, ['beras', 'kg'])) {
            return 'ask_total_rice';
        }

        if ($this->containsAny($message, ['jiwa', 'orang', 'muzakki fitrah'])) {
            return 'ask_total_people';
        }

        if ($this->containsAny($message, ['uang', 'rupiah', 'rp', 'terkumpul', 'penerimaan uang', 'nominal'])) {
            return 'ask_total_money';
        }

        if (!$this->containsAny($message, ['seberapa'])
            && $this->containsAny($message, ['berapa', 'total', 'jumlah', 'ringkasan penerimaan', 'rekap penerimaan'])) {
            return 'ask_total_summary';
        }

        if ($this->containsAny($message, ['grafik', 'harian', 'chart', 'tren', 'pola penerimaan'])) {
            return 'open_chart';
        }

        if ($this->containsAny($message, ['ringkasan', 'laporan', 'rekap'])) {
            return 'open_summary';
        }

        if ($this->containsAny($message, ['cara bayar', 'bayar zakat', 'pembayaran', 'rekening', 'transfer'])) {
            return 'ask_payment_info';
        }

        if ($this->containsAny($message, ['halo', 'helo', 'hai', 'assalamualaikum', 'assalamu', 'pagi', 'siang', 'sore', 'malam', 'zakky', 'ping']) && str_word_count($message) <= 3) {
            return 'greet';
        }

        if ($this->containsAny($message, ['lokasi', 'alamat', 'dimana masjid', 'jalan apa', 'posisi', 'maps'])) {
            return 'ask_location';
        }

        if ($this->containsAny($message, ['kontak', 'hubungi', 'no wa', 'nomor wa', 'whatsapp', 'telepon', 'telp', 'no hp'])) {
            return 'ask_contact';
        }

        $isPublicData = ($context['topic'] ?? null) === 'public_data' || ($context['last_source'] ?? null) === 'public_data';
        if ($isPublicData) {
            return $this->publicDataFollowUpIntent($message);
        }

        return null;
    }

    public function detect(string $message): ?ChatbotResponse
    {
        $intent = $this->intent($message);

        return match ($intent) {
            'open_summary' => ChatbotResponse::success(
                'Ringkasan penerimaan berisi total uang, beras, jiwa zakat fitrah, dan rincian kategori yang sudah tercatat. Jika ingin melihat angkanya, tanyakan misalnya: "Berapa total penerimaan zakat saat ini?"',
                'knowledge'
            ),
            'open_chart' => ChatbotResponse::success(
                'Grafik harian membantu membaca pola penerimaan dari hari ke hari. Saya tidak membuka tab otomatis; Anda bisa bertanya angka atau tren yang ingin dicek.',
                'knowledge'
            ),
            'greet' => ChatbotResponse::success(
                'Halo! Assalamualaikum. Saya Zakky. Ceritakan kebutuhan Anda, misalnya ingin hitung zakat fitrah, tanya zakat mal, cek fidyah, atau memahami cara pembayaran.',
                'action'
            ),
            'ask_payment_info' => ChatbotResponse::success(
                "Untuk pembayaran zakat, infaq, atau sedekah, ikuti informasi resmi panitia Masjid An-Nur.\n\n"
                . "Yang perlu disiapkan:\n"
                . "1. Jenis pembayaran: zakat fitrah, zakat mal, fidyah, atau infaq/shodaqoh.\n"
                . "2. Nama pembayar.\n"
                . "3. Nominal atau jumlah jiwa/hari.\n"
                . "4. Bukti pembayaran jika memakai transfer atau QRIS.\n\n"
                . "Pastikan nomor rekening, QRIS, atau metode pembayaran berasal dari pengumuman resmi panitia.",
                'action'
            ),
            'ask_zakky_capability' => ChatbotResponse::success(
                "Saya cukup siap untuk pertanyaan zakat yang ada di panduan Masjid An-Nur: zakat fitrah, zakat mal, fidyah, infaq/shodaqoh, cara pembayaran, ringkasan penerimaan, dan konsultasi awal kasus umum.\n\n"
                . "Untuk angka zakat mal, saya tidak menebak sendiri. Saya kumpulkan data dulu, lalu sistem menghitungnya lewat kalkulator backend agar hasilnya lebih aman. Kalau kasusnya butuh keputusan fikih pribadi, saya tetap akan arahkan ke panitia atau ustadz.",
                'knowledge',
                [],
                [['id' => 'tentang-zakky', 'label' => 'Panduan Publik Masjid An-Nur']]
            ),
            'ask_location' => ChatbotResponse::success(
                "Masjid An-Nur berlokasi di Jl. Contoh Alamat No. 123, Kelurahan Maju, Kecamatan Bersama, Kota Sejahtera.\n\n"
                . "Google Maps: [Buka di Google Maps](https://maps.app.goo.gl/o4SULwNTn9QYkQba9)",
                'action'
            ),
            'ask_contact' => ChatbotResponse::success(
                "Jika membutuhkan bantuan langsung, hubungi Panitia Zakat An-Nur.\n\n"
                . "WhatsApp/Telp: 0812-3456-7890 (Bapak Fulan)\n"
                . "Jam operasional: 08.00 - 17.00 WIB",
                'action'
            ),
            default => null,
        };
    }

    private function normalize(string $message): string
    {
        $message = preg_replace('/[^\pL\pN\s]/u', ' ', mb_strtolower($message)) ?? '';

        return trim(preg_replace('/\s+/', ' ', $message) ?? '');
    }

    private function containsAny(string $message, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function publicDataFollowUpIntent(string $message): ?string
    {
        if ($this->containsAny($message, ['terakhir', 'terbaru', 'kapan'])) {
            return 'ask_latest_update';
        }

        if ($this->containsAny($message, ['terbesar', 'terbanyak', 'tertinggi'])) {
            return 'ask_top_category';
        }

        if ($this->containsAny($message, ['semua', 'semuanya', 'totalnya', 'jumlahnya', 'ringkas'])) {
            return 'ask_total_summary';
        }

        return null;
    }
}

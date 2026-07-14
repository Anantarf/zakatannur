<?php

namespace App\Services\Chatbot;

class ChatbotActionDetector
{
    public function intent(string $message, array $context = []): ?string
    {
        $message = $this->normalize($message);

        // Check for public data totals first
        if ($this->containsAny($message, ['jiwa', 'orang', 'muzakki fitrah']) && $this->containsAny($message, ['total', 'jumlah'])) {
            return 'ask_total_people';
        }

        if ($this->containsAny($message, ['uang', 'rupiah', 'rp', 'terkumpul', 'penerimaan uang', 'nominal']) && $this->containsAny($message, ['total', 'jumlah', 'semua'])) {
            return 'ask_total_money';
        }

        if ($this->containsAny($message, ['berapa', 'total', 'jumlah', 'ringkasan penerimaan', 'rekap penerimaan']) && $this->containsAny($message, ['zakat', 'semua', 'terkumpul', 'penerimaan'])) {
            return 'ask_total_summary';
        }

        // Fitrah/Fidyah case scenarios (user asking for calculation)
        if ($this->containsAny($message, ['fitrah', 'orang', 'jiwa']) && $this->containsAny($message, ['berapa', 'hitung', 'brp']) && preg_match('/\d+/', $message)) {
            return 'calculate_fitrah_case';
        }

        if ($this->containsAny($message, ['fidyah', 'hari', 'puasa']) && $this->containsAny($message, ['berapa', 'hitung', 'brp']) && preg_match('/\d+/', $message)) {
            return 'calculate_fidyah_case';
        }


        // Zakat mal specific queries (check specific before general)
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

        if ($this->containsAny($message, ['berapa', 'total', 'jumlah', 'ringkasan penerimaan', 'rekap penerimaan'])) {
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

        if ($this->containsAny($message, ['halo', 'helo', 'hai', 'assalamualaikum', 'assalamu', 'pagi', 'siang', 'sore', 'malam', 'zakky', 'ping'])) {
            // Only if it's a very short greeting
            if (str_word_count($message) <= 3) {
                return 'greet';
            }
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
        
        if ($intent === 'open_summary') {
            return ChatbotResponse::success(
                'Saya buka tab Ringkasan Penerimaan. Di sana jamaah bisa melihat total jiwa, uang, beras, dan rincian kategori.',
                'action',
                [['type' => 'open_tab', 'target' => 'laporan', 'label' => 'Buka Ringkasan']]
            );
        }

        if ($intent === 'open_chart') {
            return ChatbotResponse::success(
                'Saya buka tab Grafik Harian. Di sana jamaah bisa melihat pola penerimaan per hari.',
                'action',
                [['type' => 'open_tab', 'target' => 'grafik', 'label' => 'Lihat Grafik']]
            );
        }
        
        if ($intent === 'greet') {
            return ChatbotResponse::success(
                'Halo! Assalamualaikum. Saya Zakky, asisten virtual Zakat An-Nur. Ada yang bisa saya bantu terkait zakat, fidyah, atau operasional masjid hari ini?',
                'action',
                [
                    ['type' => 'suggested_reply', 'label' => 'Cara bayar zakat', 'message' => 'Bagaimana cara bayar zakat?'],
                    ['type' => 'suggested_reply', 'label' => 'Laporan penerimaan', 'message' => 'Lihat ringkasan penerimaan'],
                    ['type' => 'suggested_reply', 'label' => 'Hitung zakat', 'message' => 'Bantu saya hitung zakat'],
                ]
            );
        }
        
        if ($intent === 'ask_payment_info') {
            return ChatbotResponse::success(
                "Untuk pembayaran zakat, infaq, atau sedekah, silakan transfer ke rekening resmi Masjid An-Nur:\n\n" .
                "💳 **Bank Syariah Indonesia (BSI)**\n" .
                "No. Rekening: 1234567890\n" .
                "A.n. DKM Masjid An-Nur\n\n" .
                "Atau Anda bisa datang langsung ke posko amil di Masjid An-Nur pada 10 hari terakhir bulan Ramadhan.",
                'action',
                [['type' => 'suggested_reply', 'label' => 'Lokasi masjid', 'message' => 'Di mana lokasi masjid?']]
            );
        }
        
        if ($intent === 'ask_location') {
            return ChatbotResponse::success(
                "Masjid An-Nur berlokasi di:\n\n" .
                "📍 Jl. Contoh Alamat No. 123, Kelurahan Maju, Kecamatan Bersama, Kota Sejahtera.\n\n" .
                "Google Maps: [Buka di Google Maps](https://maps.app.goo.gl/o4SULwNTn9QYkQba9)",
                'action',
                [['type' => 'suggested_reply', 'label' => 'Kontak panitia', 'message' => 'Minta nomor kontak panitia']]
            );
        }
        
        if ($intent === 'ask_contact') {
            return ChatbotResponse::success(
                "Jika Anda membutuhkan bantuan lebih lanjut atau ingin konsultasi langsung, Anda bisa menghubungi Panitia Zakat An-Nur:\n\n" .
                "📞 WhatsApp/Telp: 0812-3456-7890 (Bapak Fulan)\n" .
                "🕒 Jam operasional: 08.00 - 17.00 WIB",
                'action',
                [['type' => 'suggested_reply', 'label' => 'Cara bayar', 'message' => 'Bagaimana cara bayar zakat?']]
            );
        }

        return null;
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

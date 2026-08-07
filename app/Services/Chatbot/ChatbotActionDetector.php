<?php

namespace App\Services\Chatbot;

class ChatbotActionDetector
{
    public function intent(string $message, array $context = []): ?string
    {
        $message = $this->normalize($message);

        // Computed up front (not just further down near ask_zakat_mal_definition) because the
        // very first public-data checks below ("berapa" + "zakat") already match an extremely
        // common, natural zakat mal calculation phrasing - "gaji 10 juta, berapa zakatnya?" - and
        // would otherwise hijack it into an unrelated "total zakat terkumpul se-masjid" answer
        // before the message ever reaches any zakat-mal-specific intent check below, let alone AI.
        // The financial-keyword branch (same keyword universe as
        // ChatbotConversationContext::detectMode()'s $hasFinancialSignal) catches phrasing that
        // skips "hitung"/"konsultasi" entirely, e.g. "Zakat penghasilan saya berapa kalau gaji 8
        // juta?" - a personal figure + "berapa", not a request for the mosque's aggregate total.
        $looksLikeCalculationRequest = preg_match('/\d+/', $message) && (
            $this->containsAny($message, ['hitung', 'konsultasi'])
            || $this->containsAny($message, ['gaji', 'tabungan', 'penghasilan', 'emas', 'hutang', 'aset'])
        );

        // Narrower than $looksLikeCalculationRequest above - deliberately doesn't include
        // "hitung"/"konsultasi", since "hitung" + a digit is also how nearly every legitimate
        // fitrah/fidyah request is phrased ("hitung fitrah saya 4 orang"), so gating those
        // calculators on $looksLikeCalculationRequest itself would misroute them to AI. This only
        // catches the zakat-mal-specific words, to stop a message like "gaji saya 8 juta,
        // tanggungan 3 jiwa, hitung zakat mal saya berapa?" from being misrouted to the fitrah
        // calculator just because it mentions "jiwa".
        $hasZakatMalSignal = $this->containsAny($message, ['gaji', 'tabungan', 'penghasilan', 'emas', 'hutang', 'aset']);

        if ($this->containsAny($message, ['bisa bantu apa', 'seberapa jago', 'kemampuan', 'zakky bisa apa', 'chatbot bisa apa', 'jago bahas zakat'])) {
            return 'ask_zakky_capability';
        }

        // "orang" dropped as an anchor - it's one of the most common words in Indonesian (e.g.
        // "orang tua"), so even paired with "total"/"jumlah" it hijacked unrelated questions like
        // "Total pengeluaran rumah tangga saya untuk orang tua per bulan itu ngurangin zakat gak?".
        // "jiwa" and "muzakki fitrah" are specific enough to this domain to keep as anchors.
        if (!$looksLikeCalculationRequest && $this->containsAny($message, ['jiwa', 'muzakki fitrah']) && $this->containsAny($message, ['total', 'jumlah'])) {
            return 'ask_total_people';
        }

        if (!$looksLikeCalculationRequest && $this->containsAny($message, ['uang', 'rupiah', 'rp', 'terkumpul', 'penerimaan uang', 'nominal']) && $this->containsAny($message, ['total', 'jumlah', 'semua'])) {
            return 'ask_total_money';
        }

        // "zakat" used to be one of the second-clause options here, but it's present in nearly
        // every message about any zakat topic - paired with the loose "berapa" in the first
        // clause, that hijacked plain KB questions like "Zakat perdagangan dihitung dari modal
        // atau omzet, berapa persennya?" into an unrelated "total zakat terkumpul" answer. The
        // remaining words (semua/terkumpul/penerimaan) actually imply an aggregate/total, which
        // "zakat" alone never did.
        if (!$looksLikeCalculationRequest
            && !$this->containsAny($message, ['seberapa'])
            && $this->containsAny($message, ['berapa', 'total', 'jumlah', 'ringkasan penerimaan', 'rekap penerimaan'])
            && $this->containsAny($message, ['semua', 'terkumpul', 'penerimaan'])) {
            return 'ask_total_summary';
        }

        // "orang" dropped as an anchor here too - "Saya mau hitung THR buat 3 orang karyawan"
        // (hitung + orang + digit, nothing to do with zakat fitrah) used to match this.
        if (!$hasZakatMalSignal && $this->containsAny($message, ['fitrah', 'jiwa']) && $this->containsAny($message, ['berapa', 'hitung', 'brp']) && preg_match('/\d+/', $message)) {
            return 'calculate_fitrah_case';
        }

        // "hari" dropped as an anchor - "Ada 5 hari libur lebaran ini, mau hitung cuti tambahan
        // gimana?" (hitung + hari + digit, about leave days, not fidyah) used to match this.
        if (!$hasZakatMalSignal && $this->containsAny($message, ['fidyah', 'puasa']) && $this->containsAny($message, ['berapa', 'hitung', 'brp']) && preg_match('/\d+/', $message)) {
            return 'calculate_fidyah_case';
        }

        // Guarded the same way as ask_zakat_mal_definition below - "kasih contoh hitungan zakat mal
        // untuk gaji saya 8 juta dong" is a real calculation request with the user's own figures,
        // not a request for a generic canned example.
        if (!$looksLikeCalculationRequest && $this->containsAny($message, ['contoh', 'skenario']) && $this->containsAny($message, ['zakat', 'hitung', 'berapa'])) {
            return 'ask_zakat_mal_example';
        }

        // Unguarded, this matched "Penghasilan saya Rp8.000.000... apakah sudah mencapai nisab...
        // berapa zakat yang harus saya bayar?" too - a concrete calculation request that happens to
        // contain "nisab" + "apa"/"berapa" - and short-circuited it to the generic nisab-dan-haul KB
        // answer before it ever reached AI, so the user got the same canned explanation regardless
        // of the number they gave.
        if (!$looksLikeCalculationRequest && $this->containsAny($message, ['nishab', 'nisab']) && $this->containsAny($message, ['berapa', 'apa', 'hitung'])) {
            return 'ask_zakat_mal_nishab';
        }

        // A message like "hitungkan zakat mal saya: gaji 10 juta..." also contains the phrase
        // "zakat mal", but it's a calculation request, not a definition question - without this
        // guard it gets short-circuited to the generic KB definition before ever reaching the
        // AI consultation flow, skipping the whole guided calculation entirely.
        // ($looksLikeCalculationRequest itself is computed at the top of the function - it guards
        // the ask_total_* checks above too.)
        $asksDefinition = $this->containsAny($message, [
            'apa itu zakat mal',
            'zakat mal itu apa',
            'definisi zakat mal',
            'pengertian zakat mal',
            'apa itu zakat',
            'definisi zakat',
            'pengertian zakat',
        ]);
        $asksSpecificStyle = $this->containsAny($message, ['singkat', 'pendek', 'detail', 'rinci']);
        if (!$looksLikeCalculationRequest && !$asksSpecificStyle && $asksDefinition) {
            return 'ask_zakat_mal_definition';
        }

        if ($this->containsAny($message, ['update terakhir', 'terakhir update', 'diperbarui', 'kapan update', 'data terbaru'])) {
            return 'ask_latest_update';
        }

        // "paling besar"/"terbanyak"/"tertinggi" alone are generic comparison words - "Nisab yang
        // paling besar itu emas atau uang tunai?" and "mana yang nisabnya tertinggi?" used to
        // match this even though they're not asking about transaction categories at all. Now
        // requires "kategori" or "penerimaan" alongside the comparison word.
        if ($this->containsAny($message, ['kategori', 'penerimaan'])
            && $this->containsAny($message, ['terbesar', 'paling besar', 'terbanyak', 'tertinggi'])) {
            return 'ask_top_category';
        }

        // Requires pairing with a "recorded data" word (tercatat/terkumpul/penerimaan) - bare
        // "kategori"/"jenis zakat" used to hijack conceptual questions like "Kategori aset yang
        // kena zakat itu apa aja?" or "Jenis zakat mal yang paling sering ditanyakan apa ya?" into
        // an unrelated transaction-category dashboard answer.
        if ($this->containsAny($message, ['kategori', 'jenis zakat', 'jenis penerimaan'])
            && $this->containsAny($message, ['tercatat', 'terkumpul', 'penerimaan'])) {
            return 'ask_categories';
        }

        // "beras" is the required anchor, not "kg" - "kg" alone (even paired with "berapa"/"total")
        // is generic enough to appear in any weight question unrelated to rice zakat, e.g. a zakat
        // mal pertanian question like "panen saya 2000 kg gabah, hitungkan zakatnya berapa kg" -
        // that would otherwise get hijacked into an unrelated "total beras terkumpul" public-data
        // answer before it ever reaches AI (same class of bug as Bab 10.1, different keyword).
        // "berapa"/"kg" alone dropped as sufficient pairing too - same reasoning as ask_total_money/
        // ask_total_summary above: only an aggregate-implying word (total/terkumpul/jumlah) actually
        // signals "the mosque's total", not a bare "berapa"/"kg" which any personal harvest question
        // ("saya punya beras 800 kg... berapa zakat yang harus dikeluarkan?") also contains.
        if (!$looksLikeCalculationRequest
            && $this->containsAny($message, ['beras'])
            && $this->containsAny($message, ['total', 'jumlah', 'terkumpul'])) {
            return 'ask_total_rice';
        }

        // ask_total_people/money/summary are decided once, near the top of this function (right
        // after $looksLikeCalculationRequest) - a second, looser copy of these three checks used
        // to live here. It required no total/jumlah pairing at all for ask_total_people (bare
        // "orang" - one of the most common words in Indonesian - was enough to hijack a warisan
        // question into "total jiwa zakat fitrah"), and dropped the zakat-topic pairing for
        // ask_total_summary (bare "berapa" hijacked an unrelated address question). Removed
        // rather than kept "for safety" - the stricter versions above already cover every
        // legitimate case the loose versions were meant to catch.

        // "harian" dropped as a standalone anchor - "Petugas piket harian siapa aja ya minggu
        // ini?" used to match this even though it has nothing to do with the receipts chart.
        // "grafik"/"chart"/"tren" already cover "grafik harian" and similar phrasing on their own.
        if ($this->containsAny($message, ['grafik', 'chart', 'tren', 'pola penerimaan'])) {
            return 'open_chart';
        }

        // Requires an action verb (buka/lihat/tampilkan/cek) or a data word (penerimaan/terkumpul)
        // alongside ringkasan/laporan/rekap - bare "ringkasan" used to hijack a conceptual request
        // like "Ringkasan singkat soal zakat mal dong" (wants a short explanation, not the
        // dashboard summary feature) into an unrelated "buka ringkasan" reply.
        if ($this->containsAny($message, ['ringkasan', 'laporan', 'rekap'])
            && $this->containsAny($message, ['buka', 'lihat', 'tampilkan', 'cek', 'penerimaan', 'terkumpul'])) {
            return 'open_summary';
        }

        // Full phrases, not bare words - "rekening"/"transfer"/"cara bayar" alone used to hijack
        // "Rekening BCA punya saya kena zakat gak kalau isinya banyak?" (a zakat-mal savings
        // question) and "Cara bayar hutang riba itu gimana, ada hubungannya sama zakat?" (a debt
        // question) into an unrelated "how to pay zakat" reply.
        // !$looksLikeCalculationRequest added because "bayar zakat" is itself a substring of the
        // very natural "bayar zakat mal" - without the guard, "kapan sebaiknya saya bayar zakat mal
        // saya, gaji saya 8 juta?" (a real calculation question) got the generic payment
        // instructions instead of ever reaching AI.
        if (!$looksLikeCalculationRequest && $this->containsAny($message, [
            'cara bayar zakat', 'cara bayar infaq', 'cara bayar fidyah', 'cara bayar sedekah',
            'bayar zakat', 'pembayaran zakat', 'rekening zakat', 'transfer zakat', 'qris zakat',
        ])) {
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
        if ($isPublicData && !$looksLikeCalculationRequest) {
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
                [new ChatbotCitation('tentang-zakky', 'Panduan Publik Masjid An-Nur')]
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

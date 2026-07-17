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
        ];
    }
}

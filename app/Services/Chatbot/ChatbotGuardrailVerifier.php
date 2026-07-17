<?php

namespace App\Services\Chatbot;

class ChatbotGuardrailVerifier
{
    /**
     * Memverifikasi apakah respons LLM masih dalam konteks zakat/fidyah/infaq.
     * Jika melanggar (prompt injection / halusinasi di luar topik),
     * kembalikan respons error standar.
     */
    public function verify(string $llmReply): ?string
    {
        // Strip [HITUNG:{...}] sentinels (complete, or still mid-stream/incomplete) before
        // checking — they're internal calculation markers the LLM shouldn't be judged on, but
        // they must not blank out checking of the rest of the reply too (previously a bare
        // str_contains($llmReply, '[HITUNG:') short-circuited the ENTIRE check, so real
        // off-topic content sitting alongside a sentinel slipped through unchecked).
        $llmReply = preg_replace('/\[HITUNG:.*/is', '', $llmReply) ?? $llmReply;

        $lowerReply = strtolower($llmReply);

        // 1. Daftar kata kunci terlarang (out-of-scope / prompt injection indicators)
        // Catatan: 'saham'/'trading'/'crypto'/'bitcoin' sengaja TIDAK dimasukkan di sini —
        // itu topik zakat mal kontemporer yang sah (lihat entri KB zakat-saham-investasi-reksadana),
        // bukan cuma obrolan pasar modal. Heuristik #2 di bawah (>150 karakter tanpa kata kunci
        // domain) tetap menangkap kalau AI benar-benar melantur ke topik itu tanpa konteks zakat.
        $forbiddenTopics = [
            'resep masakan', 'cara memasak', 'bumbu', 'politik', 'pemilu', 'presiden',
            'cuaca hari ini', 'ramalan cuaca',
            'lirik lagu', 'chord gitar', 'film', 'movie', 'bioskop',
            // Prompt injection indicators
            'sebagai asisten ai umum', 'sebagai model bahasa', 'as an ai language model',
            'ignore previous instructions', 'abaikan instruksi'
        ];

        foreach ($forbiddenTopics as $topic) {
            if (str_contains($lowerReply, $topic)) {
                return "Mohon maaf, saya adalah Zakky, asisten khusus Zakat An-Nur. Saya hanya dapat menjawab pertanyaan seputar Zakat, Fidyah, Infaq, Shodaqoh, dan operasional Masjid An-Nur. Silakan ajukan pertanyaan terkait topik tersebut.";
            }
        }

        // 2. Fallback heuristic: Jika respons cukup panjang (>150 karakter) 
        // tapi TIDAK menyebut kata kunci domain kita, kemungkinan besar AI melantur akibat jailbreak.
        if (strlen($lowerReply) > 150) {
            $domainKeywords = [
                'zakat', 'fitrah', 'mal', 'fidyah', 'infaq', 'shodaqoh', 'masjid', 'an-nur', 
                'panitia', 'amil', 'mustahik', 'muzakki', 'nisab', 'nishab', 'haul', 'harta', 
                'penerimaan', 'jamaah', 'donasi', 'rupiah', 'beras', 'bayar', 'transfer'
            ];
            
            $hasDomainKeyword = false;
            foreach ($domainKeywords as $keyword) {
                if (str_contains($lowerReply, $keyword)) {
                    $hasDomainKeyword = true;
                    break;
                }
            }

            if (!$hasDomainKeyword) {
                return "Mohon maaf, sepertinya respons ini di luar jangkauan saya. Saya diprogram khusus untuk melayani konsultasi Zakat dan operasional Masjid An-Nur.";
            }
        }

        return null; // Respons aman
    }
}

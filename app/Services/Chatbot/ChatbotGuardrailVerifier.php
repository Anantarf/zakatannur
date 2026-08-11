<?php

namespace App\Services\Chatbot;

class ChatbotGuardrailVerifier
{
    /**
     * Memverifikasi apakah respons LLM masih dalam konteks zakat/fidyah/infaq.
     * Jika melanggar (prompt injection / halusinasi di luar topik),
     * kembalikan respons error standar.
     */
    public function verify(string $llmReply, ?string $mode = null): ?string
    {
        // Strip [HITUNG:{...}] sentinels (complete, or still mid-stream/incomplete) before
        // checking — they're internal calculation markers the LLM shouldn't be judged on, but
        // they must not blank out checking of the rest of the reply too (previously a bare
        // str_contains($llmReply, '[HITUNG:') short-circuited the ENTIRE check, so real
        // off-topic content sitting alongside a sentinel slipped through unchecked).
        // Bounded to the next ']' (or end of string if none exists yet, for the legitimate
        // mid-stream case) rather than a greedy '.*' to end-of-string — an unbounded strip let a
        // malformed/never-computed [HITUNG: tag (one ChatbotSentinelParser's stricter JSON regex
        // doesn't match, so it's never replaced) blind this checker to everything typed after it,
        // including real off-topic/injected content placed past a deliberately-broken tag.
        $llmReply = preg_replace('/\[HITUNG:[^\]]*\]?/is', '', $llmReply) ?? $llmReply;

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
            'ignore previous instructions', 'abaikan instruksi',
            // Distinctive fragments lifted verbatim from Zakky's own system prompt
            // (OpenAiChatbotProvider::getSystemInstruction) - a normal answer to a zakat
            // question would never say these back to the user, so seeing them in a reply
            // is a strong signal the LLM is echoing/leaking its instructions rather than
            // answering. This is deterministic (Layer 2) precisely because Layer 3's
            // embedding classifier is probabilistic and fails open when unavailable - see
            // ChatbotSafetyClassifier's confident-tier coverage caveat.
            'asisten digital zakat an-nur', 'digital assistant for zakat an-nur',
            'jangan pernah menghitung nominal zakat mal sendiri',
            'wajib hasilkan string json persis seperti ini',
        ];

        foreach ($forbiddenTopics as $topic) {
            if (str_contains($lowerReply, $topic)) {
                ChatbotDiagnostics::warning(ChatbotDiagnostics::LAYER_GUARDRAIL, 'blocked_by_keyword', [
                    'matched_keyword' => $topic,
                    'mode' => $mode,
                ]);

                return "Saya bantu untuk topik zakat dan layanan Masjid An-Nur dulu ya. Kalau mau, tanyakan soal zakat fitrah, zakat mal, fidyah, infaq/shodaqoh, atau cara bayar.";
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

            if ($mode === 'zakat_mal_consultation') {
                $domainKeywords = array_merge($domainKeywords, [
                    'rp', 'juta', 'penghasilan', 'pengeluaran', 'rutin', 'bulanan', 'bulan',
                    'tabungan', 'emas', 'hutang', 'cicilan', 'aset', 'data sementara',
                    'estimasi', 'perhitungan', 'kebutuhan hidup', 'bersih', 'wajib',
                ]);
            }
            
            // Whole-word matching, not bare str_contains() - several domain keywords are short
            // enough ("mal", and "rp" in consultation mode) to appear as a substring of completely
            // unrelated common words ("formal"/"normal"/"optimal"/"malam" all contain "mal";
            // "terperinci" contains "rp"), which let a genuinely off-topic, zero-domain-content
            // reply pass this heuristic unblocked.
            $hasDomainKeyword = false;
            foreach ($domainKeywords as $keyword) {
                if ($this->containsWholeWord($lowerReply, $keyword)) {
                    $hasDomainKeyword = true;
                    break;
                }
            }

            if (!$hasDomainKeyword) {
                ChatbotDiagnostics::warning(ChatbotDiagnostics::LAYER_GUARDRAIL, 'blocked_by_no_domain_keyword_heuristic', [
                    'reply_length' => strlen($lowerReply),
                    'mode' => $mode,
                ]);

                return "Saya bantu untuk topik zakat dan layanan Masjid An-Nur dulu ya. Kalau mau, tanyakan soal zakat fitrah, zakat mal, fidyah, infaq/shodaqoh, atau cara bayar.";
            }
        }

        return null; // Respons aman
    }

    private function containsWholeWord(string $haystack, string $needle): bool
    {
        return (bool) preg_match('/(?<![\pL\pN])' . preg_quote($needle, '/') . '(?![\pL\pN])/u', $haystack);
    }
}

<?php

namespace App\Services\Chatbot\Safety;

use App\Services\Chatbot\ChatbotDiagnostics;
use App\Services\Chatbot\Knowledge\KnowledgeEmbeddingsCache;
use App\Services\Chatbot\Providers\OpenAiEmbeddingsProvider;

class ChatbotSafetyClassifier
{
    // Similarity thresholds against the ChatbotSafetyDataset reference set, tuned via
    // `chatbot:eval-safety`'s threshold sweep (leave-one-out cross-validation over all 161 cases).
    // CONFIDENT_THRESHOLD is set to the lowest value where the in_domain false-positive rate hits
    // 0% (0.66 after switching to k-NN majority voting, see Bab 20 - the averaged vote score sits
    // on a lower scale than the old 1-NN raw max score, so this had to be re-tuned, not just the
    // classification logic) - since this classifier only ever ADDS a block on top of
    // ChatbotGuardrailVerifier's existing keyword checks, a legitimate zakat question getting
    // wrongly blocked here is worse than a nuanced risky message slipping through as
    // "ambiguous"/"no_match" (the base guardrail still catches the obvious cases). Re-run the
    // sweep and update this if the dataset or K_NEIGHBORS changes meaningfully.
    public const CONFIDENT_THRESHOLD = 0.66;
    public const AMBIGUOUS_THRESHOLD = 0.45;

    // How many nearest neighbors vote on the category (Bab 20). Pure 1-NN let a single
    // coincidentally-close example from the wrong category flip the whole classification (e.g.
    // an in_domain question about the 8 asnaf landing next to one privacy_risk example). Odd
    // count keeps ties less likely; not tuned further because the weighted vote below already
    // lets one very close neighbor outweigh several distant ones.
    private const K_NEIGHBORS = 5;

    /** Categories a confident classification should actually block a reply for. */
    private const BLOCKABLE_CATEGORIES = [
        ChatbotSafetyDataset::CATEGORY_OUT_OF_SCOPE,
        ChatbotSafetyDataset::CATEGORY_PROMPT_INJECTION,
        ChatbotSafetyDataset::CATEGORY_UNSUPPORTED_FATWA,
        ChatbotSafetyDataset::CATEGORY_PRIVACY_RISK,
        ChatbotSafetyDataset::CATEGORY_PAYMENT_VERIFICATION_RISK,
    ];

    private const REJECTION_MESSAGES = [
        ChatbotSafetyDataset::CATEGORY_OUT_OF_SCOPE => 'Saya bantu untuk topik zakat dan layanan Masjid An-Nur dulu ya. Kalau mau, tanyakan soal zakat fitrah, zakat mal, fidyah, infaq/shodaqoh, atau cara bayar.',
        ChatbotSafetyDataset::CATEGORY_PROMPT_INJECTION => 'Saya tetap Zakky, asisten zakat Masjid An-Nur, dan tidak bisa mengikuti instruksi yang mengubah peran atau membuka informasi sistem.',
        ChatbotSafetyDataset::CATEGORY_UNSUPPORTED_FATWA => 'Untuk keputusan hukum fikih yang bersifat pasti dan pribadi seperti ini, saya tidak bisa memberi vonis final. Gambaran amannya: siapkan kronologi singkat, jenis harta/ibadah yang ditanyakan, nominal atau kondisi utama, lalu konsultasikan langsung ke ustadz atau panitia Masjid An-Nur agar jawabannya sesuai kasus Anda.',
        ChatbotSafetyDataset::CATEGORY_PRIVACY_RISK => 'Saya tidak bisa membagikan data pribadi muzakki, mustahik, atau jamaah lain. Kalau kebutuhan datanya resmi, siapkan alasan permintaan dan identitas pihak yang berwenang, lalu hubungi panitia langsung agar bisa dicek lewat prosedur yang benar.',
        ChatbotSafetyDataset::CATEGORY_PAYMENT_VERIFICATION_RISK => 'Saya tidak berwenang memverifikasi, mengubah, atau membatalkan transaksi. Untuk mempercepat pengecekan panitia, siapkan bukti transfer, tanggal transfer, nominal, nama pembayar, dan jenis pembayaran yang dipilih.',
    ];

    public function __construct(
        private OpenAiEmbeddingsProvider $embeddingsProvider,
        private ChatbotSafetyEmbeddingsCache $cache
    ) {
    }

    /**
     * Classifies text against the safety dataset. Returns null if embeddings are unavailable
     * (no API key, request failure) - callers should fail OPEN (don't block) in that case, since
     * this is a supplementary layer on top of ChatbotGuardrailVerifier's keyword checks, not the
     * only line of defense.
     *
     * @return array{category: string, score: float, confidence: string}|null
     */
    public function classify(string $text): ?array
    {
        $vector = $this->embeddingsProvider->getEmbedding($text);
        if (!$vector) {
            return null;
        }

        return $this->classifyVector($vector, $this->cache->getCachedEmbeddings());
    }

    /**
     * Pure-math classification step, split out from classify() so `chatbot:eval-safety` can run
     * leave-one-out cross-validation (classify each dataset case against every OTHER case's
     * cached vector) without paying for a fresh embedding API call per case.
     *
     * @param array<float> $vector
     * @param array<int, array<float>> $referenceEmbeddings Keyed by ChatbotSafetyDataset::cases() index.
     * @return array{category: string, score: float, confidence: string}|null Null if the reference set is empty.
     */
    public function classifyVector(array $vector, array $referenceEmbeddings): ?array
    {
        if (empty($referenceEmbeddings)) {
            return null;
        }

        $cases = ChatbotSafetyDataset::cases();

        $scored = [];
        foreach ($referenceEmbeddings as $index => $refVector) {
            if (!isset($cases[$index])) {
                continue;
            }
            $scored[$index] = KnowledgeEmbeddingsCache::cosineSimilarity($vector, $refVector);
        }

        if (empty($scored)) {
            return null;
        }

        arsort($scored);
        $neighbors = array_slice($scored, 0, min(self::K_NEIGHBORS, count($scored)), true);

        // Weighted majority vote: each of the k nearest neighbors votes for its own category,
        // weighted by its similarity score, so a single very close match can still outweigh
        // several more-distant neighbors of a different category.
        $voteWeight = [];
        $voteCount = [];
        foreach ($neighbors as $index => $score) {
            $category = $cases[$index]['category'];
            $voteWeight[$category] = ($voteWeight[$category] ?? 0) + $score;
            $voteCount[$category] = ($voteCount[$category] ?? 0) + 1;
        }

        arsort($voteWeight);
        $winningCategory = array_key_first($voteWeight);
        $winningScore = $voteWeight[$winningCategory] / $voteCount[$winningCategory];

        return [
            'category' => $winningCategory,
            'score' => $winningScore,
            'confidence' => self::confidenceFor($winningScore),
        ];
    }

    public static function confidenceFor(float $score): string
    {
        return match (true) {
            $score >= self::CONFIDENT_THRESHOLD => 'confident',
            $score >= self::AMBIGUOUS_THRESHOLD => 'ambiguous',
            default => 'no_match',
        };
    }

    /**
     * Convenience wrapper for ChatbotOrchestrator: classifies a reply and returns a rejection
     * message if it confidently matches a blockable category, or null if it's safe to let through
     * (in_domain, ambiguous, no_match, or classification unavailable - all fail-open).
     */
    public function checkReply(string $reply): ?string
    {
        $result = $this->classify($reply);
        if ($result === null) {
            // Distinct from "classified but not blockable" - this is the fail-open path (no
            // embeddings API key, request failure, or empty reference set), worth knowing about
            // separately since it means Layer 3 provided zero protection for this reply.
            ChatbotDiagnostics::info(ChatbotDiagnostics::LAYER_SAFETY_CLASSIFIER, 'skipped_fail_open');
            return null;
        }

        if ($result['confidence'] !== 'confident' || !in_array($result['category'], self::BLOCKABLE_CATEGORIES, true)) {
            return null;
        }

        ChatbotDiagnostics::warning(ChatbotDiagnostics::LAYER_SAFETY_CLASSIFIER, 'blocked', [
            'category' => $result['category'],
            'score' => $result['score'],
        ]);

        return self::REJECTION_MESSAGES[$result['category']] ?? null;
    }
}

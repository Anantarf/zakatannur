<?php

namespace App\Services\Chatbot\Safety;

use App\Services\Chatbot\Knowledge\KnowledgeEmbeddingsCache;
use App\Services\Chatbot\Providers\OpenAiEmbeddingsProvider;

class ChatbotSafetyClassifier
{
    // Similarity thresholds against the ChatbotSafetyDataset reference set, tuned via
    // `chatbot:eval-safety`'s threshold sweep (leave-one-out cross-validation over all 120 cases).
    // CONFIDENT_THRESHOLD is set to the lowest value where the in_domain false-positive rate hits
    // 0% (0.68 in the last sweep) - since this classifier only ever ADDS a block on top of
    // ChatbotGuardrailVerifier's existing keyword checks, a legitimate zakat question getting
    // wrongly blocked here is worse than a nuanced risky message slipping through as
    // "ambiguous"/"no_match" (the base guardrail still catches the obvious cases). Re-run the
    // sweep and update this if the dataset changes meaningfully.
    public const CONFIDENT_THRESHOLD = 0.68;
    public const AMBIGUOUS_THRESHOLD = 0.45;

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
        ChatbotSafetyDataset::CATEGORY_UNSUPPORTED_FATWA => 'Untuk keputusan hukum fikih yang bersifat pasti dan pribadi seperti ini, saya tidak bisa memberi vonis final - silakan konsultasikan langsung ke ustadz atau panitia Masjid An-Nur.',
        ChatbotSafetyDataset::CATEGORY_PRIVACY_RISK => 'Saya tidak bisa membagikan data pribadi muzakki, mustahik, atau jamaah lain. Untuk kebutuhan data tersebut, silakan hubungi panitia langsung.',
        ChatbotSafetyDataset::CATEGORY_PAYMENT_VERIFICATION_RISK => 'Saya tidak berwenang memverifikasi, mengubah, atau membatalkan transaksi. Untuk itu, silakan hubungi panitia Masjid An-Nur langsung.',
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
        $bestIndex = null;
        $bestScore = -1.0;

        foreach ($referenceEmbeddings as $index => $refVector) {
            $score = KnowledgeEmbeddingsCache::cosineSimilarity($vector, $refVector);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIndex = $index;
            }
        }

        if ($bestIndex === null || !isset($cases[$bestIndex])) {
            return null;
        }

        return [
            'category' => $cases[$bestIndex]['category'],
            'score' => $bestScore,
            'confidence' => self::confidenceFor($bestScore),
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
        if ($result === null || $result['confidence'] !== 'confident') {
            return null;
        }

        if (!in_array($result['category'], self::BLOCKABLE_CATEGORIES, true)) {
            return null;
        }

        return self::REJECTION_MESSAGES[$result['category']] ?? null;
    }
}

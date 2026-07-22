<?php

namespace App\Services\Chatbot\Knowledge;

use App\Services\Chatbot\Providers\OpenAiEmbeddingsProvider;

class KnowledgeRetriever
{
    private const GENERIC_TITLE_WORDS = [
        'yang', 'dan', 'atau', 'untuk', 'dengan', 'dari', 'akan', 'bisa', 'ini', 'itu',
        'saya', 'anda', 'apa', 'siapa', 'kapan', 'dimana', 'bagaimana', 'gimana',
        'cara', 'jadwal', 'pada', 'oleh', 'jika', 'kalau', 'juga', 'saja',
    ];

    private OpenAiEmbeddingsProvider $embeddingsProvider;
    private KnowledgeEmbeddingsCache $embeddingsCache;

    public function __construct(OpenAiEmbeddingsProvider $embeddingsProvider, KnowledgeEmbeddingsCache $embeddingsCache)
    {
        $this->embeddingsProvider = $embeddingsProvider;
        $this->embeddingsCache = $embeddingsCache;
    }

    public function search(string $message, int $limit = 3, float $threshold = 0.45): array
    {
        $entries = \App\Models\KnowledgeBase::active()->get()->map->toKnowledgeArray()->all();

        // 1. Semantic Search via Embeddings
        $rankedViaSemantic = $this->searchViaEmbeddings($message, $entries, $threshold);
        
        if (!empty($rankedViaSemantic)) {
            // Semantic search succeeded
            return array_slice($rankedViaSemantic, 0, $limit);
        }

        // 2. Fallback to Keyword Search
        \Illuminate\Support\Facades\Log::info('KnowledgeRetriever falling back to keyword search', ['message' => $message]);
        return $this->searchViaKeywords($message, $entries, $limit);
    }

    /**
     * Melakukan pencarian semantik (Vector Search) menggunakan Cosine Similarity.
     * 
     * [EVALUASI THRESHOLD SKRIPSI]
     * Threshold 0.45 didapat dari hasil observasi jarak vektor (Cosine Similarity) pada model text-embedding-3-small:
     * - Similarity > 0.60 : Relevansi sangat tinggi (exact match/copy-paste).
     * - Similarity 0.45 - 0.59 : Relevansi moderat (pertanyaan dengan parafrase atau sinonim, misal: "cara tf" vs "pembayaran").
     * - Similarity < 0.45 : Out-of-scope atau unrelated (noise).
     * 
     * Dengan mengatur threshold di 0.45, sistem (Precision & Recall) mampu menangkap variasi bahasa gaul/singkatan jamaah
     * tanpa memasukkan dokumen yang salah sasaran.
     */
    private function searchViaEmbeddings(string $message, array $entries, float $threshold = 0.45): array
    {
        if (empty(trim($message))) {
            \Illuminate\Support\Facades\Log::warning('KnowledgeRetriever: empty message for semantic search');
            return [];
        }

        $messageEmbedding = $this->embeddingsProvider->getEmbedding($message);
        if (!$messageEmbedding) {
            \Illuminate\Support\Facades\Log::warning('KnowledgeRetriever: embedding generation failed', ['message_length' => strlen($message)]);
            return [];
        }

        $knowledgeEmbeddings = $this->embeddingsCache->getCachedEmbeddings();
        if (empty($knowledgeEmbeddings)) {
            \Illuminate\Support\Facades\Log::warning('KnowledgeRetriever: no cached embeddings available');
            return [];
        }

        $ranked = [];
        foreach ($entries as $entry) {
            $entryId = $entry['id'] ?? null;
            if (!$entryId || !isset($knowledgeEmbeddings[$entryId])) {
                continue;
            }

            $similarity = KnowledgeEmbeddingsCache::cosineSimilarity($messageEmbedding, $knowledgeEmbeddings[$entryId]);
            
            // Filter out low-relevance results immediately
            if ($similarity >= $threshold) {
                $entry['_cosine_similarity'] = $similarity;
                $ranked[] = $entry;
            }
        }

        usort($ranked, fn ($a, $b) => $b['_cosine_similarity'] <=> $a['_cosine_similarity']);

        return $ranked;
    }

    private function searchViaKeywords(string $message, array $entries, int $limit): array
    {
        $message = $this->normalize($message);
        $ranked = [];

        foreach ($entries as $entry) {
            $score = $this->score($message, $entry);
            if ($score <= 0) {
                continue;
            }

            $entry['_score'] = $score;
            $ranked[] = $entry;
        }

        usort($ranked, fn ($a, $b) => $b['_score'] <=> $a['_score']);

        return array_slice($ranked, 0, $limit);
    }

    private function score(string $message, array $entry): int
    {
        $score = 0;

        foreach (($entry['keywords'] ?? []) as $keyword) {
            $keyword = $this->normalize($keyword);
            if ($keyword === '' || !$this->containsWholeWord($message, $keyword)) {
                continue;
            }

            $isMultiWord = str_contains($keyword, ' ');
            // Single-word keywords under 4 chars (e.g. "mal") are too short to trust even with
            // whole-word matching - "malam" is a different word from "mal" but this length floor
            // is cheap insurance against the next short one.
            if (!$isMultiWord && mb_strlen($keyword) < 4) {
                continue;
            }

            $score += $isMultiWord ? 5 : 3;
        }

        foreach (explode(' ', $this->normalize((string) ($entry['title'] ?? ''))) as $token) {
            // Title tokens are a weak fallback signal (curated keywords above are the primary
            // one) - generic Indonesian function/connector words that happen to appear in a
            // title (e.g. "atau" in "Zakat Penghasilan atau Profesi") would otherwise match any
            // unrelated sentence using the same common word. See GENERIC_TITLE_WORDS.
            if (mb_strlen($token) >= 4
                && !in_array($token, self::GENERIC_TITLE_WORDS, true)
                && $this->containsWholeWord($message, $token)) {
                $score += 1;
            }
        }

        return $score;
    }

    private function normalize(string $value): string
    {
        $value = preg_replace('/[^\pL\pN\s]/u', ' ', mb_strtolower($value)) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private function containsWholeWord(string $haystack, string $needle): bool
    {
        return (bool) preg_match('/(?<![\pL\pN])' . preg_quote($needle, '/') . '(?![\pL\pN])/u', $haystack);
    }
}

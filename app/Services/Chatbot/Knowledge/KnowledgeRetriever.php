<?php

namespace App\Services\Chatbot\Knowledge;

use App\Services\Chatbot\Providers\OpenAiEmbeddingsProvider;

class KnowledgeRetriever
{
    private OpenAiEmbeddingsProvider $embeddingsProvider;
    private KnowledgeEmbeddingsCache $embeddingsCache;

    public function __construct(OpenAiEmbeddingsProvider $embeddingsProvider, KnowledgeEmbeddingsCache $embeddingsCache)
    {
        $this->embeddingsProvider = $embeddingsProvider;
        $this->embeddingsCache = $embeddingsCache;
    }

    public function search(string $message, int $limit = 3, float $threshold = 0.45): array
    {
        $entries = config('zakky_knowledge', []);

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

    public function best(string $message, float $threshold = 0.65): ?array
    {
        $results = $this->search($message, 1);
        $top = $results[0] ?? null;

        if (!$top) {
            return null;
        }

        // Check if it was ranked by semantic search
        if (isset($top['_cosine_similarity'])) {
            if ($top['_cosine_similarity'] < $threshold) {
                return null;
            }
            return $top;
        }

        // Fallback keyword score threshold check
        // The old signature used int threshold=3, we translate that here
        if ((int) ($top['_score'] ?? 0) < 3) {
            return null;
        }

        return $top;
    }

    private function searchViaEmbeddings(string $message, array $entries, float $threshold = 0.45): array
    {
        $messageEmbedding = $this->embeddingsProvider->getEmbedding($message);
        if (!$messageEmbedding) {
            return [];
        }

        $knowledgeEmbeddings = $this->embeddingsCache->getCachedEmbeddings();
        if (empty($knowledgeEmbeddings)) {
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
            if ($keyword !== '' && str_contains($message, $keyword)) {
                $score += str_contains($keyword, ' ') ? 5 : 3;
            }
        }

        foreach (explode(' ', $this->normalize((string) ($entry['title'] ?? ''))) as $token) {
            if (mb_strlen($token) >= 4 && str_contains($message, $token)) {
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
}

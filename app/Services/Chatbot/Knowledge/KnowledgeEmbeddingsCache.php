<?php

namespace App\Services\Chatbot\Knowledge;

use App\Services\Chatbot\Providers\OpenAiEmbeddingsProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class KnowledgeEmbeddingsCache
{
    private const CACHE_KEY = 'chatbot:knowledge_embeddings_v1';
    
    private OpenAiEmbeddingsProvider $embeddingsProvider;

    public function __construct(OpenAiEmbeddingsProvider $embeddingsProvider)
    {
        $this->embeddingsProvider = $embeddingsProvider;
    }

    /**
     * @return array<string, array<float>> Mapping of entry ID to its vector.
     */
    public function getCachedEmbeddings(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return $this->generateAllEmbeddings();
        });
    }

    /**
     * Force refresh the embeddings cache.
     * @return array<string, array<float>>
     */
    public function refreshCache(): array
    {
        Cache::forget(self::CACHE_KEY);
        $embeddings = $this->generateAllEmbeddings();
        if (!empty($embeddings)) {
            Cache::forever(self::CACHE_KEY, $embeddings);
        }
        return $embeddings;
    }

    private function generateAllEmbeddings(): array
    {
        $entries = config('zakky_knowledge', []);
        $vectorData = [];

        foreach ($entries as $index => $entry) {
            if (!isset($entry['id'])) {
                Log::warning('Knowledge entry missing ID during embedding generation', ['index' => $index]);
                continue;
            }

            $textToEmbed = $this->prepareTextForEmbedding($entry);
            
            $vector = $this->embeddingsProvider->getEmbedding($textToEmbed);
            if ($vector) {
                $vectorData[$entry['id']] = $vector;
            } else {
                Log::warning('Failed to generate embedding for knowledge entry', ['id' => $entry['id']]);
            }
        }

        return $vectorData;
    }

    private function prepareTextForEmbedding(array $entry): string
    {
        $title = $entry['title'] ?? '';
        $keywords = implode(', ', $entry['keywords'] ?? []);
        $answer = $entry['answer'] ?? '';
        
        return "Judul: {$title}\nKata kunci: {$keywords}\nJawaban: {$answer}";
    }

    /**
     * Calculate cosine similarity between two vectors.
     */
    public static function cosineSimilarity(array $vecA, array $vecB): float
    {
        if (empty($vecA) || empty($vecB) || count($vecA) !== count($vecB)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($vecA as $i => $valA) {
            $valB = $vecB[$i];
            $dotProduct += $valA * $valB;
            $normA += $valA * $valA;
            $normB += $valB * $valB;
        }

        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}

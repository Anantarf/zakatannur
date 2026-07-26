<?php

namespace App\Services\Chatbot\Safety;

use App\Services\Chatbot\Providers\OpenAiEmbeddingsProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChatbotSafetyEmbeddingsCache
{
    // Bump the suffix (v1 -> v2) if ChatbotSafetyDataset::cases() ever changes its item count or
    // order - the cache is keyed by array index, so a stale cache after a dataset edit would
    // silently pair the wrong category with a cached vector.
    private const CACHE_KEY = 'chatbot:safety_embeddings_v1';

    public function __construct(private OpenAiEmbeddingsProvider $embeddingsProvider)
    {
    }

    /**
     * @return array<int, array<float>> Mapping of ChatbotSafetyDataset::cases() index to its vector.
     */
    public function getCachedEmbeddings(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => $this->generateAllEmbeddings());
    }

    /** @return array<int, array<float>> */
    public function refreshCache(): array
    {
        Cache::forget(self::CACHE_KEY);
        $embeddings = $this->generateAllEmbeddings();
        if (!empty($embeddings)) {
            Cache::forever(self::CACHE_KEY, $embeddings);
        }

        return $embeddings;
    }

    /** @return array<int, array<float>> */
    private function generateAllEmbeddings(): array
    {
        if (empty(config('services.openai.api_key'))) {
            Log::info('Skipping safety embeddings: no embeddings API key configured.');

            return [];
        }

        $vectors = [];
        foreach (ChatbotSafetyDataset::cases() as $index => $case) {
            $vector = $this->embeddingsProvider->getEmbedding($case['text']);
            if ($vector) {
                $vectors[$index] = $vector;
            } else {
                Log::warning('Failed to generate safety embedding for dataset case', ['index' => $index, 'category' => $case['category']]);
            }
        }

        return $vectors;
    }
}

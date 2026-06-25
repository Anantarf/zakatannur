<?php

namespace App\Services\Chatbot\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenAiEmbeddingsProvider
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;
    private int $timeout;

    public function __construct(string $apiKey, string $baseUrl, string $model = 'text-embedding-3-small', int $timeout = 15)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->model = $model;
        $this->timeout = $timeout;
    }

    /**
     * Get embedding for a single text string.
     *
     * @param string $text
     * @return array<float>|null Returns array of floats on success, or null on failure.
     */
    public function getEmbedding(string $text): ?array
    {
        if (empty($this->apiKey) || trim($text) === '') {
            return null;
        }

        $url = "{$this->baseUrl}/embeddings";

        try {
            $response = Http::withToken($this->apiKey)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->timeout($this->timeout)
                ->connectTimeout(5)
                ->retry(2, 500)
                ->post($url, [
                    'model' => $this->model,
                    'input' => $text,
                ]);

            if ($response->successful()) {
                $embedding = $response->json('data.0.embedding');
                if (is_array($embedding) && !empty($embedding)) {
                    return $embedding;
                }
            }

            Log::error('OpenAI Embeddings Error Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (Throwable $e) {
            Log::error('OpenAI Embeddings Exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

<?php

namespace App\Services\Audit;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeminiAuditProvider
{
    private bool $lastReplyWasFallback = false;

    public function __construct(
        private string $apiKey,
        private string $model,
        private string $baseUrl,
        private int $timeout = 30
    ) {
    }

    public function generate(string $systemInstruction, string $prompt): string
    {
        $this->lastReplyWasFallback = false;

        $url = rtrim($this->baseUrl, '/') . "/{$this->model}:generateContent";

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout($this->timeout)
                ->connectTimeout(8)
                ->retry(2, 700, fn ($e) => $e instanceof ConnectionException, throw: false)
                ->post($url . '?key=' . $this->apiKey, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'systemInstruction' => [
                        'parts' => [['text' => $systemInstruction]],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'maxOutputTokens' => 800,
                    ],
                ]);

            if ($response->successful()) {
                $reply = $response->json('candidates.0.content.parts.0.text');
                if (is_string($reply) && trim($reply) !== '') {
                    return $reply;
                }
            }

            Log::error('GeminiAuditProvider: API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->fallback('Layanan AI Audit sedang tidak tersedia. Ringkasan tidak dapat dibuat saat ini.');
        } catch (Throwable $e) {
            Log::error('GeminiAuditProvider: Exception', ['message' => $e->getMessage()]);

            return $this->fallback('Layanan AI Audit mengalami kendala jaringan. Silakan coba beberapa saat lagi.');
        }
    }

    public function wasLastReplyFallback(): bool
    {
        return $this->lastReplyWasFallback;
    }

    private function fallback(string $message): string
    {
        $this->lastReplyWasFallback = true;

        return $message;
    }
}

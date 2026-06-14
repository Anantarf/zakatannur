<?php

namespace App\Services\Chatbot\Providers;

use App\Services\Chatbot\ChatbotServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GrokChatbotProvider implements ChatbotServiceInterface
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;
    private int $timeout;
    private bool $lastReplyWasFallback = false;

    public function __construct(string $apiKey, string $model, string $baseUrl, int $timeout = 25)
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    public function sendMessage(string $message, array $context = []): string
    {
        $this->lastReplyWasFallback = false;

        $systemInstruction = "Anda adalah 'Annur Support', asisten virtual resmi untuk sistem manajemen Zakat An-Nur. "
            . "Tugas Anda adalah membantu pengguna (jamaah atau petugas) terkait pengelolaan zakat, cara pembayaran, panduan nishab, dan operasional masjid. "
            . "Berikan jawaban yang singkat, ramah, dan profesional layaknya agen Customer Service SaaS. "
            . "Jika ditanya hal di luar topik zakat, agama Islam, atau operasional masjid, tolak dengan sopan dan kembalikan ke topik zakat.";

        $url = "{$this->baseUrl}/chat/completions";

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout($this->timeout)
                ->connectTimeout(8)
                ->retry(2, 600, function ($exception, $request) {
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                }, throw: false)
                ->post($url, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemInstruction],
                        ['role' => 'user', 'content' => $message],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                ]);

            if ($response->successful()) {
                $reply = $response->json('choices.0.message.content');
                if (is_string($reply) && trim($reply) !== '') {
                    return $reply;
                }

                Log::warning('Grok API returned empty reply', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'model' => $this->model,
                ]);
            }

            Log::error('Grok API Error Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'model' => $this->model,
            ]);

            return $this->fallback('Layanan asisten AI sedang tidak tersedia saat ini. Silakan coba beberapa saat lagi.');
        } catch (Throwable $e) {
            Log::error('Grok API Exception', [
                'message' => $e->getMessage(),
            ]);

            return $this->fallback('Layanan asisten AI sedang mengalami kendala jaringan. Silakan coba beberapa saat lagi.');
        }
    }

    public function wasLastReplyFallback(): bool
    {
        return $this->lastReplyWasFallback;
    }

    private function fallback(string $message): string
    {
        $this->lastReplyWasFallback = true;
        return ChatbotServiceInterface::FALLBACK_PREFIX . $message;
    }
}

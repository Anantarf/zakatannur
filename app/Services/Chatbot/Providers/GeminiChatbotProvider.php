<?php

namespace App\Services\Chatbot\Providers;

use App\Services\Chatbot\ChatbotServiceInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeminiChatbotProvider implements ChatbotServiceInterface
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

    public function sendMessage(string $message): string
    {
        $this->lastReplyWasFallback = false;

        $systemInstruction = "Nama Anda adalah 'Zakky', asisten virtual resmi untuk sistem manajemen Zakat An-Nur. "
    . "Saat memperkenalkan diri, sebutkan nama Anda sebagai Zakky dan bahwa Anda melayani Zakat An-Nur. "
    . "Tugas Anda adalah membantu pengguna (jamaah atau petugas) terkait pengelolaan zakat, cara pembayaran, panduan nishab, dan operasional masjid. "
    . "Gaya bicara: singkat, ramah, sopan, dan profesional layaknya agen Customer Service. "
    . "Jika ditanya hal di luar topik zakat, agama Islam, atau operasional masjid, tolak dengan sopan dan kembalikan ke topik zakat. "
    . "Jika pengguna menyapa (halo, assalamualaikum, hai, dll), balas sapaan dengan hangat dan perkenalkan diri sebagai Zakky.";

        $url = "{$this->baseUrl}/{$this->model}:generateContent";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->connectTimeout(8)
                ->retry(2, 700, function ($exception, $request) {
                    return $exception instanceof ConnectionException;
                }, throw: false)
                ->post($url . '?key=' . $this->apiKey, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $message]
                            ]
                        ]
                    ],
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => $systemInstruction]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 500,
                    ]
                ]);

            if ($response->successful()) {
                $reply = $response->json('candidates.0.content.parts.0.text');
                if (is_string($reply) && trim($reply) !== '') {
                    return $reply;
                }

                Log::warning('Gemini API returned empty reply', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'model' => $this->model,
                ]);
            }

            Log::error('Gemini API Error Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'model' => $this->model,
            ]);

            if ($response->status() === 401 || $response->status() === 403) {
                return $this->fallback('Layanan asisten AI belum dikonfigurasi dengan benar. Mohon hubungi admin.');
            }

            if ($response->status() === 404) {
                return $this->fallback('Model AI yang diminta tidak tersedia. Hubungi admin untuk memperbarui konfigurasi.');
            }

            if ($response->status() === 429) {
                return $this->fallback('Kuota penggunaan AI harian sudah tercapai. Silakan coba lagi besok.');
            }

            return $this->fallback('Layanan asisten AI sedang tidak tersedia saat ini. Silakan coba beberapa saat lagi.');
        } catch (Throwable $e) {
            Log::error('Gemini API Exception', [
                'message' => $e->getMessage(),
                'model' => $this->model,
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

<?php

namespace App\Services\Chatbot\Providers;

use App\Services\Chatbot\ChatbotServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiChatbotProvider implements ChatbotServiceInterface
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct(string $apiKey, string $model, string $baseUrl)
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function sendMessage(string $message): string
    {
        $systemInstruction = "Anda adalah 'Annur Support', asisten virtual resmi untuk sistem manajemen Zakat An-Nur. "
            . "Tugas Anda adalah membantu pengguna (jamaah atau petugas) terkait pengelolaan zakat, cara pembayaran, panduan nishab, dan operasional masjid. "
            . "Berikan jawaban yang singkat, ramah, dan profesional layaknya agen Customer Service SaaS. "
            . "Jika ditanya hal di luar topik zakat, agama Islam, atau operasional masjid, tolak dengan sopan dan kembalikan ke topik zakat.";

        $url = "{$this->baseUrl}/{$this->model}:generateContent";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url . '?key=' . $this->apiKey, [
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
                if (is_string($reply) && $reply !== '') {
                    return $reply;
                }
            }

            Log::error('Gemini API Error Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'model' => $this->model,
            ]);

            if ($response->status() >= 400 && $response->status() < 500) {
                return 'Mohon maaf, layanan asisten AI sedang tidak tersedia saat ini. Silakan coba beberapa saat lagi.';
            }

            return 'Mohon maaf, layanan dukungan Annur Engine sedang sibuk. Silakan coba beberapa saat lagi.';
        } catch (\Throwable $e) {
            Log::error('Gemini API Exception', [
                'message' => $e->getMessage()
            ]);

            return 'Mohon maaf, layanan dukungan Annur Engine sedang mengalami kendala jaringan. Silakan coba beberapa saat lagi.';
        }
    }
}

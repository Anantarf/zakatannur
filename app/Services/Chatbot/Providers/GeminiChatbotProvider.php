<?php

namespace App\Services\Chatbot\Providers;

use App\Services\Chatbot\ChatbotServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiChatbotProvider implements ChatbotServiceInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function sendMessage(string $message): string
    {
        $systemInstruction = "Anda adalah 'Annur Support', asisten virtual resmi untuk sistem manajemen Zakat An-Nur. "
            . "Tugas Anda adalah membantu pengguna (jamaah atau petugas) terkait pengelolaan zakat, cara pembayaran, panduan nishab, dan operasional masjid. "
            . "Berikan jawaban yang singkat, ramah, dan profesional layaknya agen Customer Service SaaS. "
            . "Jika ditanya hal di luar topik zakat, agama Islam, atau operasional masjid, tolak dengan sopan dan kembalikan ke topik zakat.";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '?key=' . $this->apiKey, [
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
                $data = $response->json();
                $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                
                if ($reply) {
                    return $reply;
                }
            }

            Log::error('Gemini API Error Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return "Mohon maaf, layanan dukungan Annur Engine sedang sibuk. Silakan coba beberapa saat lagi.";

        } catch (\Exception $e) {
            Log::error('Gemini API Exception', [
                'message' => $e->getMessage()
            ]);
            
            return "Mohon maaf, layanan dukungan Annur Engine sedang mengalami kendala jaringan. Silakan coba beberapa saat lagi.";
        }
    }
}

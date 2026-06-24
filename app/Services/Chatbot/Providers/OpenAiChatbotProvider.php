<?php

namespace App\Services\Chatbot\Providers;

use App\Services\Chatbot\ChatbotServiceInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenAiChatbotProvider implements ChatbotServiceInterface
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

        // Validate API key
        if (empty($this->apiKey)) {
            Log::error('OpenAI API key not configured', [
                'model' => $this->model,
            ]);
            return $this->fallback('Layanan asisten belum dikonfigurasi. Hubungi administrator.');
        }

        $systemInstruction = "Nama Anda adalah 'Zakky', asisten virtual untuk Zakat An-Nur. "
    . "Tugas Anda: membantu dengan pertanyaan zakat, pembayaran, nishab, dan operasional masjid. "
    . "Gaya: singkat, ramah, sopan, profesional. "
    . "PENTING: Hanya gunakan konteks yang diberikan. Jangan mengarang data (nomor rekening, jadwal, panitia, nominal). "
    . "Jika tidak tahu, katakan 'Informasi belum tersedia, hubungi panitia'. "
    . "Jika ditanya di luar topik zakat/Islam, kembalikan ke topik zakat.";

        if (!empty($context)) {
            $contextText = collect($context)
                ->map(fn ($item) => '- ' . ($item['title'] ?? 'Konteks') . ': ' . ($item['answer'] ?? ''))
                ->implode("\n");
            $systemInstruction .= "\n\nKonteks resmi:\n" . $contextText;
        }

        $url = "{$this->baseUrl}/chat/completions";

        try {
            $response = Http::withToken($this->apiKey)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->timeout($this->timeout)
                ->connectTimeout(8)
                ->retry(2, 700, function ($exception, $request) {
                    return $exception instanceof ConnectionException;
                }, throw: false)
                ->post($url, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemInstruction],
                        ['role' => 'user', 'content' => $message],
                    ],
                    'temperature' => 0.4,
                    'max_tokens' => 500,
                ]);

            if ($response->successful()) {
                $reply = $response->json('choices.0.message.content');
                if (is_string($reply) && trim($reply) !== '') {
                    return $reply;
                }

                Log::warning('OpenAI API returned empty reply', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'model' => $this->model,
                ]);
            }

            Log::error('OpenAI API Error Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'model' => $this->model,
            ]);

            if ($response->status() === 401 || $response->status() === 403) {
                Log::error('OpenAI Authentication Failed', ['status' => $response->status()]);
                return $this->fallback('Konfigurasi asisten belum lengkap. Silakan hubungi administrator.');
            }

            if ($response->status() === 404) {
                Log::error('OpenAI Model Not Found', ['model' => $this->model]);
                return $this->fallback('Model asisten tidak ditemukan. Hubungi admin untuk update konfigurasi.');
            }

            if ($response->status() === 429) {
                Log::warning('OpenAI Rate Limit Exceeded');
                return $this->fallback('Permintaan terlalu banyak. Coba lagi dalam beberapa menit.');
            }

            if ($response->status() >= 500) {
                Log::error('OpenAI Server Error', ['status' => $response->status()]);
                return $this->fallback('Server asisten mengalami masalah. Coba lagi sebentar.');
            }

            return $this->fallback('Koneksi ke asisten gagal. Periksa internet Anda.');
        } catch (Throwable $e) {
            Log::error('OpenAI API Exception', [
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

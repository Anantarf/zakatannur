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

        $systemInstruction = "Nama Anda adalah 'Zakky', asisten virtual resmi untuk sistem manajemen Zakat An-Nur. "
            . "Saat memperkenalkan diri, sebutkan nama Anda sebagai Zakky dan bahwa Anda melayani Zakat An-Nur. "
            . "Tugas Anda adalah membantu pengguna (jamaah atau petugas) terkait pengelolaan zakat, cara pembayaran, panduan nishab, dan operasional masjid. "
            . "Gaya bicara: singkat, ramah, sopan, dan profesional layaknya agen Customer Service. "
            . "Untuk informasi lokal Masjid An-Nur, gunakan hanya konteks resmi yang diberikan. "
            . "Jika konteks tidak cukup, katakan informasi belum tersedia dan arahkan pengguna untuk konfirmasi kepada panitia. "
            . "Jangan mengarang nomor rekening, jadwal, panitia, nominal, data penerimaan, atau kebijakan lokal. "
            . "Jika ditanya hal di luar topik zakat, agama Islam, atau operasional masjid, tolak dengan sopan dan kembalikan ke topik zakat. "
            . "Jika pengguna menyapa (halo, assalamualaikum, hai, dll), balas sapaan dengan hangat dan perkenalkan diri sebagai Zakky.";

        $url = "{$this->baseUrl}/chat/completions";
        $userMessage = $this->buildPrompt($message, $context);

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
                        ['role' => 'user', 'content' => $userMessage],
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

    private function buildPrompt(string $message, array $context): string
    {
        if ($context === []) {
            return $message;
        }

        $contextText = collect($context)
            ->map(fn ($item) => '- ' . ($item['title'] ?? 'Konteks') . ': ' . ($item['answer'] ?? ''))
            ->implode("\n");

        return "Konteks resmi:\n{$contextText}\n\nPertanyaan pengguna:\n{$message}";
    }

    private function fallback(string $message): string
    {
        $this->lastReplyWasFallback = true;
        return ChatbotServiceInterface::FALLBACK_PREFIX . $message;
    }
}

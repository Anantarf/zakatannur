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

    public function sendMessage(string $message, array $context = [], string $language = 'id', array $history = []): string
    {
        $this->lastReplyWasFallback = false;

        // Validate API key
        if (empty($this->apiKey)) {
            Log::error('OpenAI API key not configured', [
                'model' => $this->model,
            ]);
            return $this->fallback('Layanan asisten belum dikonfigurasi. Hubungi administrator.');
        }

        $systemInstruction = $this->getSystemInstruction($language, $context);

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
                    'messages' => $this->buildMessagesArray($systemInstruction, $history, $message),
                    'temperature' => 0.1,
                    'max_completion_tokens' => 500,
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
                return $this->fallback('Konfigurasi belum lengkap. Coba: Total uang, Total beras, Cara bayar zakat.');
            }

            if ($response->status() === 404) {
                Log::error('OpenAI Model Not Found', ['model' => $this->model]);
                return $this->fallback('Asisten sedang diperbarui. Coba tanya: Total uang, Total beras, Total jiwa.');
            }

            if ($response->status() === 429) {
                Log::warning('OpenAI Rate Limit Exceeded');
                return $this->fallback('Terlalu banyak pertanyaan. Tunggu 1 menit, lalu coba lagi.');
            }

            if ($response->status() >= 500) {
                Log::error('OpenAI Server Error', ['status' => $response->status()]);
                return $this->fallback('Server sedang sibuk. Coba dalam 1 menit atau tanya: Update terakhir.');
            }

            return $this->fallback('Koneksi bermasalah. Periksa internet atau coba pertanyaan sederhana.');
        } catch (Throwable $e) {
            Log::error('OpenAI API Exception', [
                'message' => $e->getMessage(),
                'model' => $this->model,
            ]);

            return $this->fallback('Layanan asisten AI sedang mengalami kendala jaringan. Silakan coba beberapa saat lagi.');
        }
    }

    public function streamMessage(string $message, array $context = [], string $language = 'id', array $history = []): \Generator
    {
        $this->lastReplyWasFallback = false;

        if (empty($this->apiKey)) {
            Log::error('OpenAI API key not configured', ['model' => $this->model]);
            yield $this->fallback('Layanan asisten belum dikonfigurasi. Hubungi administrator.');
            return;
        }

        $systemInstruction = $this->getSystemInstruction($language, $context);
        $url = "{$this->baseUrl}/chat/completions";

        try {
            $response = Http::withToken($this->apiKey)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'text/event-stream',
                ])
                ->timeout($this->timeout)
                ->connectTimeout(8)
                ->withOptions(['stream' => true])
                ->post($url, [
                    'model' => $this->model,
                    'messages' => $this->buildMessagesArray($systemInstruction, $history, $message),
                    'temperature' => 0.1,
                    'max_completion_tokens' => 500,
                    'stream' => true,
                ]);

            if ($response->successful()) {
                $stream = $response->toPsrResponse()->getBody();
                $buffer = '';

                while (!$stream->eof()) {
                    $buffer .= $stream->read(1024);
                    while (($pos = strpos($buffer, "\n")) !== false) {
                        $line = trim(substr($buffer, 0, $pos));
                        $buffer = substr($buffer, $pos + 1);

                        if (str_starts_with($line, 'data: ')) {
                            $data = trim(substr($line, 6));
                            if ($data === '[DONE]') {
                                break 2;
                            }

                            $json = json_decode($data, true);
                            if (isset($json['choices'][0]['delta']['content'])) {
                                yield $json['choices'][0]['delta']['content'];
                            }
                        }
                    }
                }
                return;
            }

            Log::error('OpenAI API Stream Error Response', [
                'status' => $response->status(),
                'model' => $this->model,
            ]);

            if ($response->status() === 429) {
                yield $this->fallback('Terlalu banyak pertanyaan. Tunggu 1 menit, lalu coba lagi.');
            } else {
                yield $this->fallback('Koneksi bermasalah. Periksa internet atau coba pertanyaan sederhana.');
            }
        } catch (Throwable $e) {
            Log::error('OpenAI API Stream Exception', [
                'message' => $e->getMessage(),
                'model' => $this->model,
            ]);

            yield $this->fallback('Layanan asisten AI sedang mengalami kendala jaringan. Silakan coba beberapa saat lagi.');
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

    private function getSystemInstruction(string $language, array $context): string
    {
        $systemInstruction = "You are Zakky, the digital assistant for Zakat An-Nur. "
            . "Be helpful, warm, and concise — like a knowledgeable mosque committee member. "
            . "Only answer from the 'Official Context' below. If the answer isn't there, say: "
            . "'That info isn't in my system — please contact the committee directly.' "
            . "For location or payment questions: 'Visit Masjid An-Nur during the last 10 days of Ramadan.' Only share this when asked. "
            . "Decline off-topic questions politely and redirect to zakat. "
            . "Always reply in the same language as the user. "
            . "End each reply with 2-3 follow-up suggestions: [SUGGEST: ...]";

        if ($language === 'id') {
            $systemInstruction = "Kamu adalah Zakky, asisten digital Zakat An-Nur. "
                . "Bicara seperti panitia masjid yang tahu betul soal zakat — hangat, langsung ke intinya, tidak perlu berlebihan. "
                . "Gunakan istilah awam. Jika menggunakan istilah fiqih (seperti Haul/Nishab), selalu berikan penjelasan singkat di dalam kurung. "
                . "Jawab hanya dari 'Konteks resmi' di bawah. Kalau informasinya tidak ada, bilang langsung: "
                . "'Info ini belum ada di sistem saya, lebih baik tanya langsung ke panitia.' "
                . "Kalau ditanya soal lokasi atau cara bayar, sampaikan: 'Silakan datang ke Masjid An-Nur pada 10 hari terakhir Ramadhan. "
                . "Lokasi: https://maps.app.goo.gl/o4SULwNTn9QYkQba9' — tapi hanya kalau ditanya. "
                . "Kalau pertanyaan di luar zakat/Islam/masjid, tolak dengan singkat dan kembalikan ke topik zakat. "
                . "Balas dalam bahasa yang sama dengan pertanyaan. "
                . "Di akhir balasan, tambahkan 2-3 pertanyaan lanjutan: [SUGGEST: ...]";
        }

        if (!empty($context)) {
            $contextText = collect($context)
                ->map(fn ($item) => '- ' . ($item['title'] ?? 'Konteks') . ': ' . ($item['answer'] ?? ''))
                ->implode("\n");
            $systemInstruction .= "\n\nKonteks resmi:\n" . $contextText;
        }

        return $systemInstruction;
    }

    private function buildMessagesArray(string $systemInstruction, array $history, string $currentMessage): array
    {
        $messages = [
            ['role' => 'system', 'content' => $systemInstruction],
        ];

        // Sliding Window Memory: limit to the last 3 interactions (6 messages) to save tokens and keep LLM focused
        $recentHistory = array_slice($history, -3);

        foreach ($recentHistory as $hist) {
            if (!empty($hist['question'])) {
                $messages[] = ['role' => 'user', 'content' => $hist['question']];
            }
            if (!empty($hist['answer'])) {
                $messages[] = ['role' => 'assistant', 'content' => $hist['answer']];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $currentMessage];

        return $messages;
    }
}

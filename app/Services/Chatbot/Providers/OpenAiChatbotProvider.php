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
    private string $fastModel;
    private string $premiumModel;
    private string $baseUrl;
    private int $timeout;
    private bool $lastReplyWasFallback = false;
    private array $lastUsageMetadata = [];

    public function __construct(string $apiKey, string $model, string $baseUrl, int $timeout = 25, array $models = [])
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->fastModel = $models['fast'] ?? $model;
        $this->premiumModel = $models['premium'] ?? $model;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    public function sendMessage(string $message, array $context = [], string $language = 'id', array $history = []): string
    {
        $this->lastReplyWasFallback = false;
        $this->lastUsageMetadata = [];

        // Validate API key
        if (empty($this->apiKey)) {
            Log::error('OpenAI API key not configured', [
                'model' => $this->model,
            ]);
            return $this->fallback('Layanan asisten belum dikonfigurasi. Hubungi administrator.');
        }

        $systemInstruction = $this->getSystemInstruction($language, $context);
        $selectedModel = $this->selectModel($message, $context);

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
                    'model' => $selectedModel,
                    'messages' => $this->buildMessagesArray($systemInstruction, $history, $message),
                    'temperature' => 0.1,
                    'max_completion_tokens' => 500,
                ]);

            if ($response->successful()) {
                $this->lastUsageMetadata = $this->usageMetadata($selectedModel, $response->json('usage') ?? []);
                $reply = $response->json('choices.0.message.content');
                if (is_string($reply) && trim($reply) !== '') {
                    return $reply;
                }

                Log::warning('OpenAI API returned empty reply', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'model' => $selectedModel,
                ]);
            }

            Log::error('OpenAI API Error Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'model' => $selectedModel,
            ]);

            if ($response->status() === 401 || $response->status() === 403) {
                Log::error('OpenAI Authentication Failed', ['status' => $response->status()]);
                return $this->fallback('Konfigurasi belum lengkap. Coba: Total uang, Total beras, Cara bayar zakat.');
            }

            if ($response->status() === 404) {
                Log::error('OpenAI Model Not Found', ['model' => $selectedModel]);
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
                'model' => $selectedModel,
            ]);

            return $this->fallback('Layanan asisten AI sedang mengalami kendala jaringan. Silakan coba beberapa saat lagi.');
        }
    }

    public function streamMessage(string $message, array $context = [], string $language = 'id', array $history = []): \Generator
    {
        $this->lastReplyWasFallback = false;
        $this->lastUsageMetadata = [];

        if (empty($this->apiKey)) {
            Log::error('OpenAI API key not configured', ['model' => $this->model]);
            yield $this->fallback('Layanan asisten belum dikonfigurasi. Hubungi administrator.');
            return;
        }

        $systemInstruction = $this->getSystemInstruction($language, $context);
        $selectedModel = $this->selectModel($message, $context);
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
                    'model' => $selectedModel,
                    'messages' => $this->buildMessagesArray($systemInstruction, $history, $message),
                    'temperature' => 0.1,
                    'max_completion_tokens' => 500,
                    'stream' => true,
                    'stream_options' => ['include_usage' => true],
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
                            if (isset($json['usage']) && is_array($json['usage'])) {
                                $this->lastUsageMetadata = $this->usageMetadata($selectedModel, $json['usage']);
                            }

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
                'model' => $selectedModel,
            ]);

            if ($response->status() === 429) {
                yield $this->fallback('Terlalu banyak pertanyaan. Tunggu 1 menit, lalu coba lagi.');
            } else {
                yield $this->fallback('Koneksi bermasalah. Periksa internet atau coba pertanyaan sederhana.');
            }
        } catch (Throwable $e) {
            Log::error('OpenAI API Stream Exception', [
                'message' => $e->getMessage(),
                'model' => $selectedModel,
            ]);

            yield $this->fallback('Layanan asisten AI sedang mengalami kendala jaringan. Silakan coba beberapa saat lagi.');
        }
    }

    public function wasLastReplyFallback(): bool
    {
        return $this->lastReplyWasFallback;
    }

    public function lastUsageMetadata(): array
    {
        return $this->lastUsageMetadata;
    }

    private function fallback(string $message): string
    {
        $this->lastReplyWasFallback = true;
        return ChatbotServiceInterface::FALLBACK_PREFIX . $message;
    }

    private function usageMetadata(string $model, array $usage): array
    {
        $promptTokens = (int) ($usage['prompt_tokens'] ?? 0);
        $completionTokens = (int) ($usage['completion_tokens'] ?? 0);
        $totalTokens = (int) ($usage['total_tokens'] ?? ($promptTokens + $completionTokens));

        return [
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'estimated_cost_usd' => $this->estimateCostUsd($model, $promptTokens, $completionTokens),
        ];
    }

    private function estimateCostUsd(string $model, int $promptTokens, int $completionTokens): float
    {
        $pricingPerMillion = [
            'gpt-5.6-luna' => ['input' => 1.00, 'output' => 6.00],
            'gpt-5.6-terra' => ['input' => 2.50, 'output' => 15.00],
            'gpt-5.6-sol' => ['input' => 5.00, 'output' => 30.00],
            'gpt-5.6' => ['input' => 5.00, 'output' => 30.00],
        ];

        $pricing = $pricingPerMillion[$model] ?? null;
        if ($pricing === null) {
            return 0.0;
        }

        return round(
            ($promptTokens / 1_000_000 * $pricing['input'])
            + ($completionTokens / 1_000_000 * $pricing['output']),
            8
        );
    }

    private function selectModel(string $message, array $context): string
    {
        $normalizedMessage = mb_strtolower($message);

        if ($this->needsPremiumModel($normalizedMessage, $context)) {
            return $this->premiumModel;
        }

        if ($this->canUseFastModel($normalizedMessage, $context)) {
            return $this->fastModel;
        }

        return $this->model;
    }

    private function needsPremiumModel(string $message, array $context): bool
    {
        $premiumKeywords = [
            'hitung', 'perhitungan', 'zakat mal', 'zakat maal', 'nishab', 'nisab',
            'haul', 'emas', 'tabungan', 'utang', 'hutang', 'aset', 'penghasilan',
            'gaji', 'investasi', 'saham', 'usaha', 'warisan', 'konsultasi',
        ];

        foreach ($premiumKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return count($context) >= 3 || mb_strlen($message) > 350;
    }

    private function canUseFastModel(string $message, array $context): bool
    {
        if (!empty($context) || mb_strlen($message) > 120) {
            return false;
        }

        $fastPatterns = [
            'halo', 'hai', 'assalamualaikum', 'terima kasih', 'makasih',
            'apa itu zakat', 'jadwal', 'lokasi', 'alamat', 'kontak',
            'cara bayar', 'total uang', 'total beras', 'total jiwa',
        ];

        foreach ($fastPatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }

        return str_word_count($message) <= 6;
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
            . "Do not output [SUGGEST] tags, quick replies, buttons, or UI actions. "
            . "When more detail is needed, ask one focused clarification question in plain text. "
            . "If helpful, include 2-4 numbered options plus 'Other' so the user can answer freely.";

        if ($language === 'id') {
            $systemInstruction = "Kamu adalah Zakky, asisten digital Zakat An-Nur. "
                . "Bicara seperti panitia masjid yang tahu betul soal zakat — hangat, langsung ke intinya, tidak perlu berlebihan. "
                . "Gunakan istilah awam. Jika menggunakan istilah fiqih (seperti Haul/Nishab), selalu berikan penjelasan singkat di dalam kurung. "
                . "Untuk FAQ, jawab 2-4 kalimat. Untuk konsultasi, pandu bertahap dan tanyakan satu data terpenting yang belum ada. "
                . "Sebelum menghitung, rangkum singkat data yang sudah user berikan agar kesalahan angka mudah dikoreksi. "
                . "Untuk case khusus, gunakan alur triase: identifikasi jenis harta, klasifikasikan ke kategori zakat, cek syarat utama, beri estimasi awal jika aman, sebutkan faktor yang bisa mengubah hasil, lalu beri langkah berikutnya. "
                . "Hindari terlalu sering memakai kalimat defensif seperti 'Zakky tidak menetapkan keputusan final'; gunakan redaksi lebih natural bahwa Zakky memberi arah awal dan detail kasus dapat dikonfirmasi ke panitia atau ustadz. "
                . "JANGAN membuat tag [SUGGEST], quick reply, tombol, atau instruksi UI. "
                . "Kalau butuh klarifikasi, ajukan pertanyaan dalam teks biasa. Bila cocok, beri 2-4 opsi bernomor dan opsi 'Lainnya' agar user bisa menjawab kondisi yang berbeda. "
                . "Jawab hanya dari 'Konteks resmi' di bawah. Kalau informasinya tidak ada, bilang langsung: "
                . "'Info ini belum ada di sistem saya, lebih baik tanya langsung ke panitia.' "
                . "Kalau ditanya soal lokasi atau cara bayar, sampaikan: 'Silakan datang ke Masjid An-Nur pada 10 hari terakhir Ramadhan. "
                . "Lokasi: https://maps.app.goo.gl/o4SULwNTn9QYkQba9' — tapi hanya kalau ditanya. "
                . "Kalau pertanyaan di luar zakat/Islam/masjid, tolak dengan singkat dan kembalikan ke topik zakat. "
                . "Untuk konsultasi perhitungan zakat mal, kumpulkan informasi aset (gaji bulanan, tabungan, emas, hutang, pengeluaran rutin). "
                . "Jika informasi kurang, JANGAN menebak angka, BERTANYALAH untuk melengkapi data.\n"
                . "JANGAN PERNAH menghitung nominal zakat mal sendiri. "
                . "Jika variabel cukup, WAJIB hasilkan string JSON persis seperti ini (selipkan di pesanmu): "
                . "[HITUNG:{\"income_monthly\":10000000,\"expenses_monthly\":2000000,\"savings\":50000000,\"gold_gram\":0,\"debt\":0}] "
                . "Semua kunci opsional, nilai dalam integer rupiah atau gram emas. "
                . "Balas dalam bahasa yang sama dengan pertanyaan.";
        }

        if (!empty($context)) {
            // Hint-only entries (no title/answer) carry sentiment/correction hints when no
            // knowledge context matched — they shouldn't render as an empty "- Konteks: " bullet.
            $knowledgeItems = collect($context)->filter(fn ($item) => isset($item['title']));
            if ($knowledgeItems->isNotEmpty()) {
                $contextText = $knowledgeItems
                    ->map(fn ($item) => '- ' . ($item['title'] ?? 'Konteks') . ': ' . ($item['answer'] ?? ''))
                    ->implode("\n");
                $systemInstruction .= "\n\nKonteks resmi:\n" . $contextText;
            }

            $sentimentHint = $context[0]['_sentiment_hint'] ?? null;
            if ($sentimentHint) {
                $systemInstruction .= "\n\n" . $sentimentHint;
            }

            $correctionHint = $context[0]['_correction_hint'] ?? null;
            if ($correctionHint) {
                $systemInstruction .= "\n\n" . $correctionHint;
            }

            $conversationHint = $context[0]['_conversation_hint'] ?? null;
            if ($conversationHint) {
                $systemInstruction .= "\n\n" . $conversationHint;
            }
        }

        return $systemInstruction;
    }

    private function buildMessagesArray(string $systemInstruction, array $history, string $currentMessage): array
    {
        $messages = [
            ['role' => 'system', 'content' => $systemInstruction],
        ];

        // Sliding Window Memory: keep the last 8 interactions for multi-turn consultation.
        $recentHistory = array_slice($history, -8);

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

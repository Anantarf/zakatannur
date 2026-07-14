<?php

namespace App\Services\Chatbot;

use App\Models\AiChatLog;
use App\Services\Chatbot\Knowledge\KnowledgeRetriever;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Support\Facades\Cache;

class ChatbotOrchestrator
{
    public function __construct(
        private ChatbotServiceInterface $aiProvider,
        private ChatbotActionDetector $actionDetector,
        private KnowledgeRetriever $knowledgeRetriever,
        private ChatbotPublicDataResponder $publicDataResponder
    ) {
    }

    public function handle(string $message, array $rawContext = [], ?string $sessionId = null): ChatbotResponse
    {
        $quickResponse = $this->getQuickResponse($message, $rawContext, $sessionId);
        if ($quickResponse) {
            return $quickResponse;
        }

        try {
            $sentiment = $this->detectSentiment($message);
            $response = $this->answerFromAi($message, $sessionId);
            $confidenceSource = $this->aiProvider->wasLastReplyFallback() ? 'fallback' : 'ai';
            $this->saveChatLog($message, null, $response->source, $response->reply, $sessionId, $sentiment, $confidenceSource);

            // Cache jalur AI dimatikan sesuai spesifikasi (RAG memory butuh stateful)
            return $response;
        } catch (Throwable $e) {
            Log::error('Chatbot orchestration failed.', [
                'message' => $e->getMessage(),
            ]);

            return ChatbotResponse::error('Gagal memproses pesan. Silakan coba beberapa saat lagi.', true, 500);
        }
    }

    public function stream(string $message, array $rawContext = [], ?string $sessionId = null): \Generator
    {
        $quickResponse = $this->getQuickResponse($message, $rawContext, $sessionId);
        if ($quickResponse) {
            yield ['response' => $quickResponse];
            return;
        }

        try {
            $sentiment = $this->detectSentiment($message);
            $generator = $this->streamFromAi($message, $sessionId, $sentiment);

            $fullReply = '';
            $responseObj = null;

            foreach ($generator as $chunk) {
                if (is_array($chunk) && isset($chunk['response'])) {
                    $responseObj = $chunk['response'];
                } else if (is_string($chunk)) {
                    $fullReply .= $chunk;
                    yield ['chunk' => $chunk];
                }
            }

            $confidenceSource = $this->aiProvider->wasLastReplyFallback() ? 'fallback' : 'ai';
            $this->saveChatLog($message, null, $responseObj->source, $fullReply, $sessionId, $sentiment, $confidenceSource);

            // Cache jalur AI dimatikan sesuai spesifikasi (RAG memory butuh stateful)

            yield ['response' => $responseObj];
        } catch (Throwable $e) {
            Log::error('Chatbot stream orchestration failed.', ['message' => $e->getMessage()]);
            yield ['response' => ChatbotResponse::error('Gagal memproses pesan. Silakan coba beberapa saat lagi.', true, 500)];
        }
    }

    private function getQuickResponse(string $message, array $rawContext = [], ?string $sessionId = null): ?ChatbotResponse
    {
        // Check cache for identical messages within the same conversation context
        $cached = Cache::get($this->cacheKey($message, $rawContext, $sessionId));
        if ($cached) {
            $this->saveChatLog($message, 'cached', $cached->source ?? 'cache', $cached->reply, $sessionId);
            return $cached;
        }

        $context = $this->buildContext($rawContext);

        try {
            $intent = $this->actionDetector->intent($message, $context);

            // Handle fitrah/fidyah calculation cases
            if ($intent === 'calculate_fitrah_case') {
                return $this->finalizeQuickResponse($this->calculateFitrah($message), $message, $rawContext, $intent, 'calculation', $sessionId);
            }

            if ($intent === 'calculate_fidyah_case') {
                return $this->finalizeQuickResponse($this->calculateFidyah($message), $message, $rawContext, $intent, 'calculation', $sessionId);
            }

            // Route specific zakat mal intents to their knowledge base entries
            if (in_array($intent, ['ask_zakat_mal_definition', 'ask_zakat_mal_nishab', 'ask_zakat_mal_example'])) {
                $entryId = match($intent) {
                    'ask_zakat_mal_definition', 'ask_zakat_mal_nishab' => 'zakat-mal-definisi',
                    'ask_zakat_mal_example' => 'zakat-mal-contoh',
                    default => null,
                };

                if ($entryId) {
                    $knowledge = \App\Models\KnowledgeBase::active()->where('slug', $entryId)->first()?->toKnowledgeArray();

                    if ($knowledge) {
                        $response = ChatbotResponse::success(
                            (string) $knowledge['answer'],
                            'knowledge',
                            $knowledge['actions'] ?? [],
                            [['id' => $knowledge['id'], 'label' => $knowledge['source_label'] ?? 'Panduan Zakat Masjid An-Nur']]
                        )->withContext($this->contextForIntent($context, $entryId, 'knowledge'));
                        return $this->finalizeQuickResponse($response, $message, $rawContext, $intent, 'knowledge', $sessionId, 'knowledge');
                    }
                }
            }

            $publicData = $intent ? $this->publicDataResponder->respond($intent) : null;
            if ($publicData) {
                $response = $publicData->withContext($this->contextForIntent($context, $intent, 'public_data'));
                return $this->finalizeQuickResponse($response, $message, $rawContext, $intent, 'public_data', $sessionId, 'knowledge');
            }

            $action = $this->actionDetector->detect($message);
            if ($action) {
                $response = $action->withContext($this->contextForIntent($context, 'navigation', 'action'));
                return $this->finalizeQuickResponse($response, $message, $rawContext, 'navigation', 'action', $sessionId, null);
            }

            return null;
        } catch (Throwable $e) {
            Log::error('Quick response failed.', [
                'message' => $e->getMessage(),
            ]);

            return ChatbotResponse::error('Gagal memproses pesan. Silakan coba beberapa saat lagi.', true, 500);
        }
    }

    private function finalizeQuickResponse(ChatbotResponse $response, string $message, array $rawContext, ?string $intent, string $sourceType, ?string $sessionId, ?string $confidenceSource = 'calculation'): ChatbotResponse
    {
        $this->saveChatLog($message, $intent, $sourceType, $response->reply, $sessionId, null, $confidenceSource);
        Cache::put($this->cacheKey($message, $rawContext, $sessionId), $response, 3600);
        return $response;
    }

    private function saveChatLog(string $question, ?string $intent, string $sourceType, string $answer, ?string $sessionId, ?string $sentiment = null, ?string $confidenceSource = null): void
    {
        try {
            AiChatLog::updateOrCreate(
                [
                    'session_id' => $sessionId,
                    'question_md5' => md5($question),
                ],
                [
                    'question' => $question,
                    'intent' => $intent,
                    'source_type' => $sourceType,
                    'answer' => $answer,
                    'sentiment' => $sentiment,
                    'confidence_source' => $confidenceSource,
                ]
            );
        } catch (Throwable $e) {
            Log::warning('Failed to save AI chat log.', ['message' => $e->getMessage()]);
        }
    }

    private function buildHistory(?string $sessionId): array
    {
        if (!$sessionId) {
            return [];
        }

        $recentLogs = AiChatLog::where('session_id', $sessionId)
            ->whereNotNull('answer')
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get()
            ->reverse();

        $history = [];
        foreach ($recentLogs as $log) {
            $history[] = [
                'question' => $log->question,
                'answer' => $log->answer,
            ];
        }

        return $history;
    }

    private function applySentimentHint(array $contexts, string $sentiment): array
    {
        if ($sentiment === 'frustrated' && !empty($contexts)) {
            $contexts[0] = array_merge($contexts[0], [
                '_sentiment_hint' => 'User appears frustrated. Be empathetic, concise, and offer clear next steps.',
            ]);
        }

        return $contexts;
    }

    private function finalizeAiReply(string $rawReply, bool $wasFallback, array $contexts): ChatbotResponse
    {
        $cleanReply = $wasFallback && str_starts_with($rawReply, ChatbotServiceInterface::FALLBACK_PREFIX)
            ? substr($rawReply, strlen(ChatbotServiceInterface::FALLBACK_PREFIX))
            : $rawReply;

        $actions = [];

        // Include hardcoded actions from the highest ranked context if available
        if (!empty($contexts[0]['actions'])) {
            $actions = array_merge($actions, $contexts[0]['actions']);
        }

        if (!$wasFallback) {
            preg_match_all('/\[SUGGEST:\s*(.*?)\]/i', $cleanReply, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $suggestText) {
                    // Prevent duplicate labels if AI hallucinates the exact same suggestion as hardcoded
                    $isDuplicate = collect($actions)->contains('label', trim($suggestText));
                    if (!$isDuplicate) {
                        $actions[] = [
                            'type' => 'suggested_reply',
                            'label' => trim($suggestText),
                            'message' => trim($suggestText),
                        ];
                    }
                }
                $cleanReply = trim(preg_replace('/\[SUGGEST:\s*.*?\]/i', '', $cleanReply));
            }
        }

        $cleanReply = $this->parseAndCalculateSentinel($cleanReply);

        return $wasFallback
            ? ChatbotResponse::error($cleanReply, true)
            : ChatbotResponse::success($cleanReply, 'ai', $actions, $contexts);
    }

    private function parseAndCalculateSentinel(string $reply): string
    {
        if (preg_match('/\[HITUNG:\s*(\{.*?\})\s*\]/is', $reply, $matches)) {
            $jsonStr = $matches[1];
            $data = json_decode($jsonStr, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                // Rusak
                $replacement = "\n\n(Mohon maaf, saya kurang mengerti datanya. Bisa sebutkan nominal penghasilan bulanan, tabungan, dan emas yang dimiliki?)";
            } else {
                // Cek negatif
                $hasNegative = false;
                $allEmpty = true;
                foreach (['income_monthly', 'expenses_monthly', 'savings', 'gold_gram', 'debt'] as $key) {
                    if (isset($data[$key])) {
                        $allEmpty = false;
                        if ((int)$data[$key] < 0) {
                            $hasNegative = true;
                        }
                    }
                }

                if ($hasNegative) {
                    $replacement = "\n\n(Pastikan nominal yang Anda masukkan tidak kurang dari nol. Mari coba hitung ulang.)";
                } elseif ($allEmpty) {
                    $replacement = "\n\n(Bisa sebutkan nominal penghasilan atau tabungannya agar bisa saya hitung?)";
                } else {
                    // Semua valid, panggil calculate()
                    $year = (int) \App\Models\AppSetting::getInt(\App\Models\AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);
                    $defaultsResolver = app(\App\Services\Transactions\AnnualZakatDefaultsResolver::class);
                    $defaults = $defaultsResolver->resolve($year);
                    
                    $guide = app(ChatbotZakatMalGuide::class);
                    $result = $guide->calculate($data, $defaults);

                    if (!$result['is_above_nishab']) {
                        $replacement = sprintf(
                            "\n\n*Estimasi Zakat Mal:*\n"
                            . "• Aset neto: Rp %s\n"
                            . "• Nishab (batas wajib): Rp %s\n\n"
                            . "Kesimpulan: Saat ini belum wajib zakat mal.",
                            number_format($result['nett_assets'], 0, ',', '.'),
                            number_format($result['nishab'], 0, ',', '.')
                        );
                    } else {
                        $replacement = sprintf(
                            "\n\n*Estimasi Zakat Mal:*\n"
                            . "• Total aset kotor: Rp %s\n"
                            . "• Hutang & pengeluaran tahunan: Rp %s\n"
                            . "• Aset neto: Rp %s\n"
                            . "• Nishab: Rp %s\n\n"
                            . "Kesimpulan: Wajib zakat mal.\n"
                            . "Total zakat (2.5%%): Rp %s per tahun (~Rp %s/bulan)",
                            number_format($result['total_assets'], 0, ',', '.'),
                            number_format($result['annual_expenses'] + $result['debt'], 0, ',', '.'),
                            number_format($result['nett_assets'], 0, ',', '.'),
                            number_format($result['nishab'], 0, ',', '.'),
                            number_format($result['zakat_amount'], 0, ',', '.'),
                            number_format((int)($result['zakat_amount'] / 12), 0, ',', '.')
                        );
                    }
                }
            }

            $reply = trim(str_replace($matches[0], $replacement, $reply));
        }

        return $reply;
    }

    private function answerFromAi(string $message, ?string $sessionId): ChatbotResponse
    {
        $language = $this->detectLanguage($message);
        $sentiment = $this->detectSentiment($message);
        $contexts = $this->applySentimentHint($this->knowledgeRetriever->search($message, 2), $sentiment);
        $history = $this->buildHistory($sessionId);

        $reply = $this->aiProvider->sendMessage($message, $contexts, $language, $history);
        $wasFallback = $this->aiProvider->wasLastReplyFallback();

        return $this->finalizeAiReply($reply, $wasFallback, $contexts);
    }

    private function streamFromAi(string $message, ?string $sessionId, string $sentiment): \Generator
    {
        $language = $this->detectLanguage($message);
        $contexts = $this->applySentimentHint($this->knowledgeRetriever->search($message, 2), $sentiment);
        $history = $this->buildHistory($sessionId);

        $stream = $this->aiProvider->streamMessage($message, $contexts, $language, $history);

        $fullReply = '';
        $buffer = '';
        $isSwallowing = false;
        $swallowingType = null;

        foreach ($stream as $chunk) {
            $fullReply .= $chunk;
            $buffer .= $chunk;

            while (strlen($buffer) > 0) {
                if ($isSwallowing) {
                    $pos = strpos($buffer, ']');
                    if ($pos !== false) {
                        $isSwallowing = false;
                        $sentinel = substr($buffer, 0, $pos + 1);
                        $buffer = substr($buffer, $pos + 1);

                        // [HITUNG:...] must surface its computed result live; [SUGGEST:...] stays hidden (becomes actions later)
                        if ($swallowingType === 'hitung') {
                            $computed = trim($this->parseAndCalculateSentinel($sentinel));
                            if ($computed !== '') {
                                yield $computed;
                            }
                        }
                        $swallowingType = null;
                    } else {
                        break; // Wait for ]
                    }
                } else {
                    $pos = strpos($buffer, '[');
                    if ($pos !== false) {
                        $yieldStr = substr($buffer, 0, $pos);
                        if ($yieldStr !== '') {
                            yield $yieldStr;
                            $buffer = substr($buffer, $pos);
                        }

                        $prefix9 = substr($buffer, 0, 9);
                        $prefix8 = substr($buffer, 0, 8);

                        if (strlen($buffer) < 9) {
                            if (!str_starts_with("[SUGGEST:", strtoupper($prefix9)) && !str_starts_with("[HITUNG:", strtoupper($prefix8))) {
                                yield '[';
                                $buffer = substr($buffer, 1);
                            } else {
                                break; // Wait for more chars
                            }
                        } else {
                            if (strtoupper($prefix9) === '[SUGGEST:') {
                                $isSwallowing = true;
                                $swallowingType = 'suggest';
                            } elseif (strtoupper($prefix8) === '[HITUNG:') {
                                $isSwallowing = true;
                                $swallowingType = 'hitung';
                            } else {
                                yield '[';
                                $buffer = substr($buffer, 1);
                            }
                        }
                    } else {
                        yield $buffer;
                        $buffer = '';
                    }
                }
            }
        }
        if ($buffer !== '' && !$isSwallowing) {
            yield $buffer;
        }

        $wasFallback = $this->aiProvider->wasLastReplyFallback();
        $response = $this->finalizeAiReply($fullReply, $wasFallback, $contexts);

        yield ['response' => $response];
    }

    private function extractNumberFromText(string $text, array $keywords): ?int
    {
        $normalized = strtolower($text);
        
        // 1. Try to find a digit near the keyword
        foreach ($keywords as $keyword) {
            if (preg_match('/(\d+)[\s]*' . preg_quote($keyword) . '/i', $normalized, $matches)) {
                return (int) $matches[1];
            }
        }
        
        // 2. Try to map words to numbers near keyword
        $map = [
            'satu' => 1, 'dua' => 2, 'tiga' => 3, 'empat' => 4, 'lima' => 5,
            'enam' => 6, 'tujuh' => 7, 'delapan' => 8, 'sembilan' => 9, 'sepuluh' => 10,
            'sebelas' => 11, 'dua belas' => 12
        ];
        
        foreach ($keywords as $keyword) {
            foreach ($map as $word => $num) {
                if (preg_match('/' . preg_quote($word) . '[\s]*' . preg_quote($keyword) . '/i', $normalized)) {
                    return $num;
                }
            }
        }
        
        // 3. Fallback: just try to find any number in the message
        if (preg_match('/(\d+)/', $normalized, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function calculateFitrah(string $message): ChatbotResponse
    {
        $count = $this->extractNumberFromText($message, ['orang', 'jiwa', 'person']);
        
        if (!$count) {
            return ChatbotResponse::success(
                'Berapa orang yang mau dihitung fitrahnya? Coba ketik: "Fitrah 4 orang berapa?"',
                'knowledge',
                [['type' => 'suggested_reply', 'label' => 'Contoh', 'message' => 'Fitrah keluarga 4 orang berapa?']]
            );
        }
        $cashPerJiwa = config('zakat.annual_defaults.fitrah_cash_per_jiwa', 50000);
        $berasPerJiwa = config('zakat.annual_defaults.fitrah_beras_per_jiwa', 2.5);

        $totalCash = $count * $cashPerJiwa;
        $totalBeras = $count * $berasPerJiwa;

        $reply = sprintf(
            "Fitrah untuk %d orang:\n\n"
            . "Uang  : %d × Rp %s = Rp %s\n"
            . "Beras : %d × %.1f kg = %.1f kg\n\n"
            . "Angka ini mengacu tarif An-Nur tahun ini. Konfirmasi ke panitia sebelum bayar ya.",
            $count,
            $count, number_format($cashPerJiwa, 0, ',', '.'), number_format($totalCash, 0, ',', '.'),
            $count, $berasPerJiwa, $totalBeras
        );

        return ChatbotResponse::success($reply, 'calculation');
    }

    private function calculateFidyah(string $message): ChatbotResponse
    {
        $days = $this->extractNumberFromText($message, ['hari', 'day']);
        
        if (!$days) {
            return ChatbotResponse::success(
                'Berapa hari fidyahnya? Coba ketik: "Fidyah 7 hari berapa?"',
                'knowledge',
                [['type' => 'suggested_reply', 'label' => 'Contoh', 'message' => 'Fidyah 5 hari berapa?']]
            );
        }
        $cashPerHari = config('zakat.annual_defaults.fidyah_per_hari', 30000);
        $berasPerHari = config('zakat.annual_defaults.fidyah_beras_per_hari', 0.75);

        $totalCash = $days * $cashPerHari;
        $totalBeras = $days * $berasPerHari;

        $reply = sprintf(
            "Fidyah untuk %d hari:\n\n"
            . "Uang  : %d × Rp %s = Rp %s\n"
            . "Beras : %d × %.2f kg = %.2f kg\n\n"
            . "Angka ini mengacu tarif An-Nur tahun ini. Konfirmasi ke panitia sebelum bayar ya.",
            $days,
            $days, number_format($cashPerHari, 0, ',', '.'), number_format($totalCash, 0, ',', '.'),
            $days, $berasPerHari, $totalBeras
        );

        return ChatbotResponse::success($reply, 'calculation');
    }



    private function buildContext(array $rawContext): array
    {
        return [
            'last_intent' => is_string($rawContext['last_intent'] ?? null) ? trim($rawContext['last_intent']) : null,
            'last_source' => is_string($rawContext['last_source'] ?? null) ? trim($rawContext['last_source']) : null,
            'topic' => is_string($rawContext['topic'] ?? null) ? trim($rawContext['topic']) : null,
        ];
    }

    private function contextForIntent(array $context, string $intent, string $source): array
    {
        $topic = 'general';
        if ($source === 'public_data' || str_starts_with($intent, 'ask_')) {
            $topic = 'public_data';
        } elseif ($source === 'knowledge') {
            $topic = 'knowledge';
        } elseif ($source === 'action') {
            $topic = 'navigation';
        }

        return array_filter([
            'last_intent' => $intent,
            'last_source' => $source,
            'topic' => $topic,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function cacheKey(string $message, array $rawContext = [], ?string $sessionId = null): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', preg_replace('/[^\pL\pN\s]/u', ' ', mb_strtolower($message))));
        $context = $this->buildContext($rawContext);
        $contextPart = implode('|', [
            $context['last_intent'] ?? '',
            $context['last_source'] ?? '',
            $context['topic'] ?? '',
        ]);
        $hash = md5($normalized . '|' . $contextPart . '|' . ($sessionId ?? ''));
        return "chatbot:response:{$hash}";
    }

    private function detectLanguage(string $message): string
    {
        $lower = strtolower($message);
        $englishWords = [
            'how', 'what', 'when', 'where', 'why', 'who', 'is', 'are', 'do', 'does',
            'can', 'should', 'would', 'could', 'will', 'have', 'has', 'been', 'total',
            'collected', 'much', 'many', 'money', 'rice', 'pay', 'help', 'information',
            'please', 'thank', 'hello', 'hi', 'yes', 'no', 'tell', 'show', 'give',
            'chart', 'graph', 'data', 'report', 'summary', 'account', 'transaction',
            'history', 'status', 'update', 'latest', 'current', 'recent', 'check',
        ];

        $englishCount = 0;
        foreach ($englishWords as $word) {
            if (preg_match('/\b' . preg_quote($word) . '\b/', $lower)) {
                $englishCount++;
            }
        }

        $wordCount = count(array_filter(str_word_count($lower, 1)));
        $englishRatio = $wordCount > 0 ? $englishCount / $wordCount : 0;

        return $englishRatio > 0.3 ? 'en' : 'id';
    }

    private function detectSentiment(string $message): string
    {
        $lower = strtolower($message);

        $frustratedWords = [
            'tidak bisa', 'error', 'gagal', 'kenapa', 'kenape', 'gak bisa',
            'masa', 'ndak bisa', 'kok', 'mana', 'mbok', 'bodo', 'bingung sekali',
            'ngasal', 'salah', 'broken', 'not working', 'failed',
            'why', 'why not', 'useless', 'stupid', 'sucks',
        ];

        $confusedWords = [
            'bagaimana', 'gimana', 'apa itu', 'maksudnya', 'bingung',
            'gimana cara', 'caranya', 'bagaimana cara', 'apa bedanya',
            'how to', 'how do', 'what is', 'what does', 'confused',
            'don\'t understand', 'unclear', 'tidak paham', 'tidak mengerti',
        ];

        foreach ($frustratedWords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return 'frustrated';
            }
        }

        foreach ($confusedWords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return 'confused';
            }
        }

        return 'neutral';
    }
}

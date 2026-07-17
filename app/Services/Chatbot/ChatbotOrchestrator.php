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
        private ChatbotPublicDataResponder $publicDataResponder,
        private ChatbotGuardrailVerifier $guardrailVerifier,
        private ChatbotLanguageDetector $languageDetector,
        private ChatbotSentimentDetector $sentimentDetector,
        private ChatbotCalculatorService $calculatorService,
        private ChatbotSentinelParser $sentinelParser
    ) {
    }

    public function handle(string $message, array $rawContext = [], ?string $sessionId = null): ChatbotResponse
    {
        $quickResponse = $this->getQuickResponse($message, $rawContext, $sessionId);
        if ($quickResponse) {
            return $quickResponse;
        }

        try {
            $sentiment = $this->sentimentDetector->detect($message);
            $response = $this->answerFromAi($message, $rawContext, $sessionId);
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
            $sentiment = $this->sentimentDetector->detect($message);
            $generator = $this->streamFromAi($message, $rawContext, $sessionId, $sentiment);

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

        // Once the AI has taken over a conversation (e.g. mid zakat-mal consultation), stay in
        // AI mode for continuity. Otherwise a reply like "kambingnya 40 ekor, sudah berapa lama"
        // can contain "berapa"/"total"-style words the fast-path keyword matcher looks for,
        // then get hijacked into an unrelated public-data answer instead of continuing the chat.
        if (($context['last_source'] ?? null) === 'ai') {
            return null;
        }

        try {
            $intent = $this->actionDetector->intent($message, $context);

            // Handle fitrah/fidyah calculation cases
            if ($intent === 'calculate_fitrah_case') {
                return $this->finalizeQuickResponse($this->calculatorService->calculateFitrah($message), $message, $rawContext, $intent, 'calculation', $sessionId);
            }

            if ($intent === 'calculate_fidyah_case') {
                return $this->finalizeQuickResponse($this->calculatorService->calculateFidyah($message), $message, $rawContext, $intent, 'calculation', $sessionId);
            }

            // Route specific zakat mal intents to their knowledge base entries
            if (in_array($intent, ['ask_zakat_mal_definition', 'ask_zakat_mal_nishab', 'ask_zakat_mal_example'])) {
                $entryId = match($intent) {
                    'ask_zakat_mal_definition', 'ask_zakat_mal_nishab', 'ask_zakat_mal_example' => 'zakat-mal',
                    default => null,
                };

                if ($entryId) {
                    $knowledge = \App\Models\KnowledgeBase::active()->where('slug', $entryId)->first()?->toKnowledgeArray();

                    if ($knowledge) {
                        $response = ChatbotResponse::success(
                            (string) $knowledge['answer'],
                            'knowledge',
                            [],
                            [['id' => $knowledge['id'], 'label' => $knowledge['source_label'] ?? 'Panduan Zakat Masjid An-Nur']]
                        )->withContext($this->contextForIntent($entryId, 'knowledge'));
                        return $this->finalizeQuickResponse($response, $message, $rawContext, $intent, 'knowledge', $sessionId, 'knowledge');
                    }
                }
            }

            $publicData = $intent ? $this->publicDataResponder->respond($intent) : null;
            if ($publicData) {
                $response = $publicData->withContext($this->contextForIntent($intent, 'public_data'));
                return $this->finalizeQuickResponse($response, $message, $rawContext, $intent, 'public_data', $sessionId, 'knowledge');
            }

            $action = $this->actionDetector->detect($message);
            if ($action) {
                $response = $action->withContext($this->contextForIntent($intent ?? 'chatbot_info', $action->source));
                return $this->finalizeQuickResponse($response, $message, $rawContext, $intent ?? 'chatbot_info', $action->source, $sessionId, null);
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
                    // Keyed on the raw question so two different questions that happen to
                    // redact to the same text (e.g. two different nominals) never collide.
                    'question_md5' => md5($question),
                ],
                [
                    'question' => $this->redactNominals($question),
                    'intent' => $intent,
                    'source_type' => $sourceType,
                    'answer' => $this->redactNominals($answer),
                    'sentiment' => $sentiment,
                    'confidence_source' => $confidenceSource,
                ]
            );
        } catch (Throwable $e) {
            Log::warning('Failed to save AI chat log.', ['message' => $e->getMessage()]);
        }
    }

    // Zakat mal consultation routinely surfaces income/debt/savings figures — mask anything that
    // looks like a money amount before it lands in ai_chat_logs, so a jamaah's financial details
    // don't sit in plain text indefinitely. Intent/topic/sentiment stay analyzable either way.
    private function redactNominals(string $text): string
    {
        return preg_replace('/\d[\d.,]{5,}\d|\b\d{6,}\b/', '[nominal]', $text) ?? $text;
    }

    private function buildHistory(?string $sessionId): array
    {
        if (!$sessionId) {
            return [];
        }

        $recentLogs = AiChatLog::where('session_id', $sessionId)
            ->whereNotNull('answer')
            ->orderBy('created_at', 'desc')
            ->limit(8)
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
        if ($sentiment === 'frustrated') {
            $contexts = $this->mergeHintIntoContexts($contexts, [
                '_sentiment_hint' => 'User appears frustrated. Be empathetic, concise, and offer clear next steps.',
            ]);
        }

        return $contexts;
    }

    private function mergeHintIntoContexts(array $contexts, array $hint): array
    {
        if (empty($contexts)) {
            $contexts[] = $hint;
        } else {
            $contexts[0] = array_merge($contexts[0], $hint);
        }

        return $contexts;
    }

    private function applyCorrectionHint(array $contexts, string $message): array
    {
        if ($this->sentimentDetector->isCorrectingPreviousNumber($message)) {
            $contexts = $this->mergeHintIntoContexts($contexts, [
                '_correction_hint' => 'User tampaknya sedang mengoreksi angka yang sudah disebut sebelumnya. '
                    . 'GANTI nilai lama itu dengan nilai baru, jangan menjumlahkan keduanya.',
            ]);
        }

        return $contexts;
    }

    private function applyConversationHint(array $contexts, string $mode): array
    {
        if ($mode !== 'zakat_mal_consultation') {
            return $contexts;
        }

        return $this->mergeHintIntoContexts($contexts, [
            '_conversation_hint' => 'Mode percakapan: konsultasi zakat mal. '
                . 'Rangkum singkat data yang sudah diberikan user, tanyakan hanya data penting yang belum ada, '
                . 'dan jangan mengulang penjelasan umum kecuali diminta. Jika data belum cukup, beri opsi bernomor seperti '
                . '1) tidak ada hutang, 2) ada hutang jatuh tempo, 3) ada cicilan, 4) lainnya. '
                . 'Jika data sudah cukup, gunakan [HITUNG:{...}].',
        ]);
    }

    private function detectConversationMode(string $message, array $rawContext): string
    {
        $context = $this->buildContext($rawContext);
        $normalized = mb_strtolower($message);

        $hasZakatMal = str_contains($normalized, 'zakat mal')
            || str_contains($normalized, 'zakat maal')
            || str_contains($normalized, 'hitung zakat')
            || str_contains($normalized, 'nisab')
            || str_contains($normalized, 'nishab');

        $hasFinancialSignal = preg_match('/\d/', $normalized)
            || str_contains($normalized, 'gaji')
            || str_contains($normalized, 'tabungan')
            || str_contains($normalized, 'emas')
            || str_contains($normalized, 'hutang')
            || str_contains($normalized, 'pengeluaran')
            || str_contains($normalized, 'aset');

        if (($context['mode'] ?? null) === 'zakat_mal_consultation') {
            // Stay for short/ambiguous follow-ups (bare numbers, "tidak ada hutang", "iya") since
            // those carry no topic keyword of their own. But let an explicit switch to another
            // zakat topic actually leave the mode - otherwise a user asking about zakat fitrah or
            // the payment schedule mid-consultation gets stuck being asked for more zakat mal data
            // instead of an answer to what they just asked.
            $switchesTopic = str_contains($normalized, 'zakat fitrah')
                || str_contains($normalized, 'fidyah')
                || str_contains($normalized, 'jadwal')
                || str_contains($normalized, 'dashboard')
                || str_contains($normalized, 'grafik')
                || str_contains($normalized, 'infaq')
                || str_contains($normalized, 'shodaqoh')
                || str_contains($normalized, 'cara bayar')
                || str_contains($normalized, 'konfirmasi');

            if (!$switchesTopic || $hasZakatMal || $hasFinancialSignal) {
                return 'zakat_mal_consultation';
            }
        }

        return $hasZakatMal || $hasFinancialSignal ? 'zakat_mal_consultation' : 'general';
    }

    private function aiConversationContext(string $mode): array
    {
        return array_filter([
            'last_source' => 'ai',
            'topic' => $mode === 'zakat_mal_consultation' ? 'zakat_mal' : 'ai_conversation',
            'mode' => $mode,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function finalizeAiReply(string $rawReply, bool $wasFallback, array $contexts, string $mode): ChatbotResponse
    {
        $cleanReply = $wasFallback && str_starts_with($rawReply, ChatbotServiceInterface::FALLBACK_PREFIX)
            ? substr($rawReply, strlen(ChatbotServiceInterface::FALLBACK_PREFIX))
            : $rawReply;

        if (!$wasFallback) {
            $cleanReply = $this->polishReply($cleanReply);
        }

        $cleanReply = $this->sentinelParser->parseAndCalculateSentinel($cleanReply);
        $cleanReply = $this->polishReply($cleanReply);

        // --- Guardrail Verification ---
        // Verifikasi output untuk memastikan tidak ada prompt injection atau halusinasi di luar topik zakat
        $guardrailViolation = $this->guardrailVerifier->verify($cleanReply);
        if ($guardrailViolation !== null) {
            $cleanReply = $guardrailViolation;
            return ChatbotResponse::error($cleanReply, false, 403);
        }

        return $wasFallback
            ? ChatbotResponse::error($cleanReply, true)
            // last_source:'ai' round-trips through the frontend's context and back on the next
            // request, so getQuickResponse() can tell "we're mid AI-conversation" and defer to
            // the AI instead of the fast-path keyword matcher hijacking the user's next reply.
            : ChatbotResponse::success($cleanReply, 'ai', [], $contexts)
                ->withContext($this->aiConversationContext($mode));
    }

    private function polishReply(string $reply): string
    {
        $reply = trim(preg_replace('/\[SUGGEST:\s*.*?\]/i', '', $reply) ?? $reply);
        $reply = trim(preg_replace('/\[(OPEN_TAB|ACTION|BUTTON):.*?\]/i', '', $reply) ?? $reply);
        $reply = preg_replace('/\n{3,}/', "\n\n", $reply) ?? $reply;

        return trim($reply);
    }

    private function answerFromAi(string $message, array $rawContext, ?string $sessionId): ChatbotResponse
    {
        $language = $this->languageDetector->detect($message);
        $sentiment = $this->sentimentDetector->detect($message);
        $mode = $this->detectConversationMode($message, $rawContext);
        // 3, not 2: a question spanning two KB topics (e.g. "warisan rumah yang saya sewakan")
        // needs both entries to survive the cut - one extra slot is cheap insurance against a
        // higher-scoring unrelated entry crowding one of them out. Real fix would be multi-hop
        // retrieval, but that's a new LLM round-trip for a problem this mostly solves already.
        $contexts = $this->applySentimentHint($this->knowledgeRetriever->search($message, 3), $sentiment);
        $contexts = $this->applyCorrectionHint($contexts, $message);
        $contexts = $this->applyConversationHint($contexts, $mode);
        $history = $this->buildHistory($sessionId);

        $reply = $this->aiProvider->sendMessage($message, $contexts, $language, $history);
        $wasFallback = $this->aiProvider->wasLastReplyFallback();

        return $this->finalizeAiReply($reply, $wasFallback, $contexts, $mode);
    }

    private function streamFromAi(string $message, array $rawContext, ?string $sessionId, string $sentiment): \Generator
    {
        $language = $this->languageDetector->detect($message);
        $mode = $this->detectConversationMode($message, $rawContext);
        // 3, not 2: a question spanning two KB topics (e.g. "warisan rumah yang saya sewakan")
        // needs both entries to survive the cut - one extra slot is cheap insurance against a
        // higher-scoring unrelated entry crowding one of them out. Real fix would be multi-hop
        // retrieval, but that's a new LLM round-trip for a problem this mostly solves already.
        $contexts = $this->applySentimentHint($this->knowledgeRetriever->search($message, 3), $sentiment);
        $contexts = $this->applyCorrectionHint($contexts, $message);
        $contexts = $this->applyConversationHint($contexts, $mode);
        $history = $this->buildHistory($sessionId);

        $stream = $this->aiProvider->streamMessage($message, $contexts, $language, $history);

        $fullReply = '';
        $buffer = '';
        $sentenceBuffer = '';
        $isSwallowing = false;
        $swallowingType = null;
        $guardrailTripped = false;

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

                        // [HITUNG:...] must surface its computed result live; [SUGGEST:...] stays hidden.
                        if ($swallowingType === 'hitung') {
                            $computed = trim($this->sentinelParser->parseAndCalculateSentinel($sentinel));
                            if ($computed !== '') {
                                $sentenceBuffer .= $computed;
                            }
                            // Replace it in $fullReply too, so finalizeAiReply's later pass finds
                            // no sentinel left and doesn't redo the same DB lookup + calculation.
                            $fullReply = str_replace($sentinel, $computed, $fullReply);
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
                            $sentenceBuffer .= $yieldStr;
                            $buffer = substr($buffer, $pos);
                        }

                        $prefix9 = substr($buffer, 0, 9);
                        $prefix8 = substr($buffer, 0, 8);

                        if (strlen($buffer) < 9) {
                            if (!str_starts_with("[SUGGEST:", strtoupper($prefix9)) && !str_starts_with("[HITUNG:", strtoupper($prefix8))) {
                                $sentenceBuffer .= '[';
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
                                $sentenceBuffer .= '[';
                                $buffer = substr($buffer, 1);
                            }
                        }
                    } else {
                        $sentenceBuffer .= $buffer;
                        $buffer = '';
                    }
                }
            }

            // Flush complete sentences only, so a guardrail violation is caught at a
            // sentence boundary instead of after an arbitrary run of raw provider chunks —
            // shrinks the window of unsafe content a user could see before it's blocked.
            foreach ($this->extractCompleteSentences($sentenceBuffer) as $sentence) {
                if ($this->guardrailVerifier->verify($fullReply) !== null) {
                    $guardrailTripped = true;
                    break;
                }
                yield $sentence;
            }

            if ($guardrailTripped) {
                break;
            }
        }

        if (!$guardrailTripped) {
            if ($buffer !== '' && !$isSwallowing) {
                $sentenceBuffer .= $buffer;
            }
            if ($sentenceBuffer !== '' && $this->guardrailVerifier->verify($fullReply) === null) {
                yield $sentenceBuffer;
            }
        }

        $wasFallback = $this->aiProvider->wasLastReplyFallback();
        $response = $this->finalizeAiReply($fullReply, $wasFallback, $contexts, $mode);

        yield ['response' => $response];
    }

    private function extractCompleteSentences(string &$sentenceBuffer): array
    {
        $sentences = [];

        while (true) {
            if (preg_match('/^.*?[.!?\n]/s', $sentenceBuffer, $matches)) {
                $sentences[] = $matches[0];
                $sentenceBuffer = substr($sentenceBuffer, strlen($matches[0]));
                continue;
            }

            if (strlen($sentenceBuffer) > 200) {
                $sentences[] = $sentenceBuffer;
                $sentenceBuffer = '';
            }

            break;
        }

        return $sentences;
    }

    private function buildContext(array $rawContext): array
    {
        return [
            'last_intent' => is_string($rawContext['last_intent'] ?? null) ? trim($rawContext['last_intent']) : null,
            'last_source' => is_string($rawContext['last_source'] ?? null) ? trim($rawContext['last_source']) : null,
            'topic' => is_string($rawContext['topic'] ?? null) ? trim($rawContext['topic']) : null,
            'mode' => is_string($rawContext['mode'] ?? null) ? trim($rawContext['mode']) : null,
        ];
    }

    private function contextForIntent(string $intent, string $source): array
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
            $context['mode'] ?? '',
        ]);
        $hash = md5($normalized . '|' . $contextPart . '|' . ($sessionId ?? ''));
        return "chatbot:response:{$hash}";
    }
}

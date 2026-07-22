<?php

namespace App\Services\Chatbot;

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
        private ChatbotSentinelParser $sentinelParser,
        private ChatbotChatLogger $chatLogger,
        private ChatbotConversationContext $conversationContext
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
            $this->chatLogger->save($message, null, $response->source, $response->reply, $sessionId, $sentiment, $confidenceSource, $this->aiProvider->lastUsageMetadata());

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

            // streamFromAi() always yields a 'response' item as its last item under every current
            // code path, but this guards against a future provider implementation that ends its
            // generator early without one - a null-property crash is worse than a generic error.
            if ($responseObj === null) {
                $responseObj = ChatbotResponse::error('Gagal memproses pesan. Silakan coba beberapa saat lagi.', true, 500);
            }

            $confidenceSource = $this->aiProvider->wasLastReplyFallback() ? 'fallback' : 'ai';
            $this->chatLogger->save($message, null, $responseObj->source, $fullReply, $sessionId, $sentiment, $confidenceSource, $this->aiProvider->lastUsageMetadata());

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
        $cached = Cache::get($this->conversationContext->cacheKey($message, $rawContext, $sessionId));
        if ($cached) {
            $this->chatLogger->save($message, 'cached', $cached->source ?? 'cache', $cached->reply, $sessionId);
            return $cached;
        }

        $context = $this->conversationContext->parse($rawContext);

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
                    'ask_zakat_mal_definition', 'ask_zakat_mal_example' => 'zakat-mal',
                    'ask_zakat_mal_nishab' => 'nisab-dan-haul',
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
                        )->withContext($this->conversationContext->forIntent($entryId, 'knowledge'));
                        return $this->finalizeQuickResponse($response, $message, $rawContext, $intent, 'knowledge', $sessionId, 'knowledge');
                    }
                }
            }

            $publicData = $intent ? $this->publicDataResponder->respond($intent) : null;
            if ($publicData) {
                $response = $publicData->withContext($this->conversationContext->forIntent($intent, 'public_data'));
                return $this->finalizeQuickResponse($response, $message, $rawContext, $intent, 'public_data', $sessionId, 'knowledge');
            }

            $action = $this->actionDetector->detect($message);
            if ($action) {
                $response = $action->withContext($this->conversationContext->forIntent($intent ?? 'chatbot_info', $action->source));
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
        $this->chatLogger->save($message, $intent, $sourceType, $response->reply, $sessionId, null, $confidenceSource);
        Cache::put($this->conversationContext->cacheKey($message, $rawContext, $sessionId), $response, 3600);
        return $response;
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
        $guardrailViolation = $this->guardrailVerifier->verify($cleanReply, $mode);
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
                ->withContext($this->conversationContext->aiContext($mode));
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
        $mode = $this->conversationContext->detectMode($message, $rawContext);
        $contexts = $this->conversationContext->withHints($this->retrieveContexts($message, $rawContext, $mode), $message, $sentiment, $mode);
        $history = $this->chatLogger->history($sessionId);

        $reply = $this->aiProvider->sendMessage($message, $contexts, $language, $history);
        $wasFallback = $this->aiProvider->wasLastReplyFallback();

        return $this->finalizeAiReply($reply, $wasFallback, $contexts, $mode);
    }

    private function streamFromAi(string $message, array $rawContext, ?string $sessionId, string $sentiment): \Generator
    {
        $language = $this->languageDetector->detect($message);
        $mode = $this->conversationContext->detectMode($message, $rawContext);
        $contexts = $this->conversationContext->withHints($this->retrieveContexts($message, $rawContext, $mode), $message, $sentiment, $mode);
        $history = $this->chatLogger->history($sessionId);

        $stream = $this->aiProvider->streamMessage($message, $contexts, $language, $history);

        $parser = new ChatbotStreamParser($this->sentinelParser, $this->guardrailVerifier, $mode);
        foreach ($parser->parse($stream) as $sentence) {
            yield $sentence;
        }

        $wasFallback = $this->aiProvider->wasLastReplyFallback();
        $response = $this->finalizeAiReply($parser->fullReply(), $wasFallback, $contexts, $mode);

        yield ['response' => $response];
    }

    private function retrieveContexts(string $message, array $rawContext, string $mode): array
    {
        $wasAlreadyConsulting = ($rawContext['mode'] ?? null) === 'zakat_mal_consultation';

        // A short reply continuing an already-active consultation ("iya benar", "tidak ada
        // hutang", "50 juta") almost never needs fresh KB grounding - ChatbotConversationContext's
        // conversation hint (mode instructions) is injected below regardless of whether any
        // entries come back, so skipping the embedding+cosine-search round-trip here just saves
        // ~1s per turn in the most common part of a consultation without losing any instruction.
        // Risk: a genuine KB-worthy tangent phrased in <=8 words goes ungrounded for that one
        // turn - acceptable, since full chat history is still passed to the model regardless.
        if ($mode === 'zakat_mal_consultation' && $wasAlreadyConsulting && str_word_count($message) <= 8) {
            return [];
        }

        // 3, not 2: a question spanning two KB topics (e.g. "warisan rumah yang saya sewakan")
        // needs both entries to survive the cut - one extra slot is cheap insurance against a
        // higher-scoring unrelated entry crowding one of them out. Real fix would be multi-hop
        // retrieval, but that's a new LLM round-trip for a problem this mostly solves already.
        return $this->knowledgeRetriever->search($message, 3);
    }
}

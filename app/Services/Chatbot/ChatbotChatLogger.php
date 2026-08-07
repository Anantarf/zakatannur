<?php

namespace App\Services\Chatbot;

use App\Models\AiChatLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatbotChatLogger
{
    // Conversation memory used to rebuild LLM context lives here, separate from ai_chat_logs.
    // ai_chat_logs is redacted before it's written (see redactNominals()) so financial figures
    // never sit in permanent storage - but the live conversation still needs the real figures
    // the user typed, or the model starts echoing "[nominal]" back as if it were a literal value.
    // This cache is short-lived (session-scoped, not permanent), so keeping raw text here doesn't
    // reintroduce the long-term-storage privacy risk the redaction was added for.
    private const CONVERSATION_CACHE_PREFIX = 'chatbot:conversation:';
    private const CONVERSATION_CACHE_TTL_SECONDS = 1800;
    private const CONVERSATION_HISTORY_LIMIT = 8;

    public function save(string $question, ?string $intent, string $sourceType, string $answer, ?string $sessionId, ?string $sentiment = null, ?string $confidenceSource = null, array $usage = []): void
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
                    'model' => $usage['model'] ?? null,
                    'prompt_tokens' => $usage['prompt_tokens'] ?? null,
                    'cached_tokens' => $usage['cached_tokens'] ?? null,
                    'completion_tokens' => $usage['completion_tokens'] ?? null,
                    'total_tokens' => $usage['total_tokens'] ?? null,
                    'estimated_cost_usd' => $usage['estimated_cost_usd'] ?? null,
                ]
            );

            $this->rememberConversationTurn($sessionId, $question, $answer);
        } catch (Throwable $e) {
            Log::warning('Failed to save AI chat log.', ['message' => $e->getMessage()]);
        }
    }

    public function history(?string $sessionId): array
    {
        if (!$sessionId) {
            return [];
        }

        return Cache::get(self::conversationCacheKey($sessionId), []);
    }

    public static function conversationCacheKey(string $sessionId): string
    {
        return self::CONVERSATION_CACHE_PREFIX . $sessionId;
    }

    private function rememberConversationTurn(?string $sessionId, string $question, string $answer): void
    {
        if (!$sessionId) {
            return;
        }

        $key = self::conversationCacheKey($sessionId);
        $history = Cache::get($key, []);
        $history[] = ['question' => $question, 'answer' => $answer];
        $history = array_slice($history, -self::CONVERSATION_HISTORY_LIMIT);

        Cache::put($key, $history, self::CONVERSATION_CACHE_TTL_SECONDS);
    }

    // Zakat mal consultation routinely surfaces income/debt/savings figures — mask anything that
    // looks like a money amount before it lands in ai_chat_logs, so a jamaah's financial details
    // don't sit in plain text indefinitely. Intent/topic/sentiment stay analyzable either way.
    private function redactNominals(string $text): string
    {
        return preg_replace('/\d[\d.,]{5,}\d|\b\d{6,}\b/', '[nominal]', $text) ?? $text;
    }
}

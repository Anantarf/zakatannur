<?php

namespace App\Services\Chatbot;

use App\Models\AiChatLog;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatbotChatLogger
{
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
                    'completion_tokens' => $usage['completion_tokens'] ?? null,
                    'total_tokens' => $usage['total_tokens'] ?? null,
                    'estimated_cost_usd' => $usage['estimated_cost_usd'] ?? null,
                ]
            );
        } catch (Throwable $e) {
            Log::warning('Failed to save AI chat log.', ['message' => $e->getMessage()]);
        }
    }

    public function history(?string $sessionId): array
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

    // Zakat mal consultation routinely surfaces income/debt/savings figures — mask anything that
    // looks like a money amount before it lands in ai_chat_logs, so a jamaah's financial details
    // don't sit in plain text indefinitely. Intent/topic/sentiment stay analyzable either way.
    private function redactNominals(string $text): string
    {
        return preg_replace('/\d[\d.,]{5,}\d|\b\d{6,}\b/', '[nominal]', $text) ?? $text;
    }
}

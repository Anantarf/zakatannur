<?php

namespace App\Services\Chatbot;

use App\Models\AiChatLog;
use App\Services\Chatbot\Knowledge\KnowledgeRetriever;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Services\Chatbot\ChatbotResponseCache;

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
        // Check cache for identical messages
        $cached = ChatbotResponseCache::get($message);
        if ($cached) {
            $this->saveChatLog($message, 'cached', 'cache', $cached->reply, $sessionId);
            return $cached;
        }

        $context = ChatbotConversationContext::fromArray($rawContext);

        try {
            $intent = $this->actionDetector->intent($message, $context);
            $publicData = $intent ? $this->publicDataResponder->respond($intent) : null;
            if ($publicData) {
                $response = $publicData->withContext($context->forIntent($intent, 'public_data')->toArray());
                $this->saveChatLog($message, $intent, 'public_data', $response->reply, $sessionId);
                ChatbotResponseCache::put($message, $response);
                return $response;
            }

            $action = $this->actionDetector->detect($message);
            if ($action) {
                $response = $action->withContext($context->forIntent('navigation', 'action')->toArray());
                $this->saveChatLog($message, 'navigation', 'action', $response->reply, $sessionId);
                ChatbotResponseCache::put($message, $response);
                return $response;
            }

            $knowledge = $this->knowledgeRetriever->best($message);
            if ($knowledge) {
                $response = ChatbotResponse::success(
                    (string) $knowledge['answer'],
                    'knowledge',
                    $knowledge['actions'] ?? [],
                    [[
                        'id' => $knowledge['id'] ?? null,
                        'label' => $knowledge['source_label'] ?? 'Panduan Zakat Masjid An-Nur',
                    ]]
                )->withContext($context->forIntent((string) ($knowledge['id'] ?? 'knowledge'), 'knowledge')->toArray());
                $this->saveChatLog($message, (string) ($knowledge['id'] ?? 'knowledge'), 'knowledge', $response->reply, $sessionId);
                ChatbotResponseCache::put($message, $response);
                return $response;
            }

            $response = $this->answerFromAi($message);
            $this->saveChatLog($message, null, $response->source, $response->reply, $sessionId);
            ChatbotResponseCache::put($message, $response);
            return $response;
        } catch (Throwable $e) {
            Log::error('Chatbot orchestration failed.', [
                'message' => $e->getMessage(),
            ]);

            return ChatbotResponse::error('Gagal memproses pesan. Silakan coba beberapa saat lagi.', true, 500);
        }
    }

    private function saveChatLog(string $question, ?string $intent, string $sourceType, string $answer, ?string $sessionId): void
    {
        try {
            AiChatLog::create([
                'session_id' => $sessionId,
                'question' => $question,
                'intent' => $intent,
                'source_type' => $sourceType,
                'answer' => $answer,
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to save AI chat log.', ['message' => $e->getMessage()]);
        }
    }

    private function answerFromAi(string $message): ChatbotResponse
    {
        $language = ChatbotLanguageDetector::detect($message);
        $contexts = $this->knowledgeRetriever->search($message, 2);
        $reply = $this->aiProvider->sendMessage($message, $contexts, $language);

        $wasFallback = $this->aiProvider->wasLastReplyFallback();
        $cleanReply = $wasFallback && str_starts_with($reply, ChatbotServiceInterface::FALLBACK_PREFIX)
            ? substr($reply, strlen(ChatbotServiceInterface::FALLBACK_PREFIX))
            : $reply;

        if ($wasFallback) {
            return ChatbotResponse::error($cleanReply, true, 503);
        }

        return ChatbotResponse::success($cleanReply, 'ai');
    }
}

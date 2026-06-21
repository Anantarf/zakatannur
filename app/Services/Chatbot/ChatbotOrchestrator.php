<?php

namespace App\Services\Chatbot;

use App\Services\Chatbot\Knowledge\KnowledgeRetriever;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatbotOrchestrator
{
    public function __construct(
        private ChatbotServiceInterface $aiProvider,
        private ChatbotActionDetector $actionDetector,
        private KnowledgeRetriever $knowledgeRetriever,
        private ChatbotPublicDataResponder $publicDataResponder
    ) {
    }

    public function handle(string $message, array $rawContext = []): ChatbotResponse
    {
        $context = ChatbotConversationContext::fromArray($rawContext);

        try {
            $intent = $this->actionDetector->intent($message, $context);
            $publicData = $intent ? $this->publicDataResponder->respond($intent) : null;
            if ($publicData) {
                return $publicData->withContext($context->forIntent($intent, 'public_data')->toArray());
            }

            $action = $this->actionDetector->detect($message);
            if ($action) {
                return $action->withContext($context->forIntent('navigation', 'action')->toArray());
            }

            $knowledge = $this->knowledgeRetriever->best($message);
            if ($knowledge) {
                return ChatbotResponse::success(
                    (string) $knowledge['answer'],
                    'knowledge',
                    $knowledge['actions'] ?? [],
                    [[
                        'id' => $knowledge['id'] ?? null,
                        'label' => $knowledge['source_label'] ?? 'Panduan Zakat Masjid An-Nur',
                    ]]
                )->withContext($context->forIntent((string) ($knowledge['id'] ?? 'knowledge'), 'knowledge')->toArray());
            }

            return $this->answerFromAi($message);
        } catch (Throwable $e) {
            Log::error('Chatbot orchestration failed.', [
                'message' => $e->getMessage(),
            ]);

            return ChatbotResponse::error('Gagal memproses pesan. Silakan coba beberapa saat lagi.', true, 500);
        }
    }

    private function answerFromAi(string $message): ChatbotResponse
    {
        $contexts = $this->knowledgeRetriever->search($message, 2);
        $reply = $this->aiProvider->sendMessage($message, $contexts);

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

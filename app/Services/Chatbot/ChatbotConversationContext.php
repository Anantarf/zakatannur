<?php

namespace App\Services\Chatbot;

class ChatbotConversationContext
{
    public function __construct(
        private ?string $lastIntent = null,
        private ?string $lastSource = null,
        private ?string $topic = null
    ) {
    }

    public static function fromArray(array $context): self
    {
        return new self(
            self::stringOrNull($context['last_intent'] ?? null),
            self::stringOrNull($context['last_source'] ?? null),
            self::stringOrNull($context['topic'] ?? null)
        );
    }

    public function forIntent(string $intent, string $source): self
    {
        return new self($intent, $source, $this->topicFor($intent, $source));
    }

    public function isPublicDataTopic(): bool
    {
        return $this->topic === 'public_data' || $this->lastSource === 'public_data';
    }

    public function toArray(): array
    {
        return array_filter([
            'last_intent' => $this->lastIntent,
            'last_source' => $this->lastSource,
            'topic' => $this->topic,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function topicFor(string $intent, string $source): string
    {
        if ($source === 'public_data' || str_starts_with($intent, 'ask_')) {
            return 'public_data';
        }

        if ($source === 'knowledge') {
            return 'knowledge';
        }

        if ($source === 'action') {
            return 'navigation';
        }

        return 'general';
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}

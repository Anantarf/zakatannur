<?php

namespace App\Services\Chatbot;

class ChatbotResponse
{
    public string $reply;
    public string $source;
    public array $actions;
    public array $citations;
    public array $context;
    public bool $retryable;
    public int $statusCode;

    public function __construct(
        string $reply,
        string $source = 'ai',
        array $actions = [],
        array $citations = [],
        array $context = [],
        bool $retryable = false,
        int $statusCode = 200
    ) {
        $this->reply = $reply;
        $this->source = $source;
        $this->actions = $actions;
        $this->citations = $citations;
        $this->context = $context;
        $this->retryable = $retryable;
        $this->statusCode = $statusCode;
    }

    public static function success(string $reply, string $source = 'ai', array $actions = [], array $citations = []): self
    {
        return new self($reply, $source, $actions, $citations);
    }

    public function withContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    public static function error(string $reply, bool $retryable = true, int $statusCode = 503): self
    {
        return new self($reply, 'fallback', [], [], [], $retryable, $statusCode);
    }

    public function toArray(): array
    {
        if ($this->statusCode >= 400) {
            return [
                'status' => 'error',
                'message' => $this->reply,
                'retryable' => $this->retryable,
            ];
        }

        return [
            'status' => 'success',
            'data' => [
                'reply' => $this->reply,
                'source' => $this->source,
                'actions' => $this->actions,
                'citations' => $this->citations,
                'context' => $this->context,
            ],
        ];
    }
}

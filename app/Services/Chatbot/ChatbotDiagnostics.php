<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Log;

// Single point of truth for "which layer did what" across the chatbot pipeline, so a failure or
// unexpected block can be traced to a specific layer (fast-path routing, RAG retrieval, LLM call,
// sentinel calculation, guardrail, safety classifier) instead of guessing from a generic error
// message. Every entry lands in the 'chatbot' log channel (storage/logs/chatbot.log) tagged with
// a layer name, so `grep '\[guardrail\]'` (or any layer) isolates exactly that layer's activity.
class ChatbotDiagnostics
{
    public const LAYER_ACTION_DETECTOR = 'action_detector';
    public const LAYER_KNOWLEDGE_RETRIEVER = 'knowledge_retriever';
    public const LAYER_LLM_PROVIDER = 'llm_provider';
    public const LAYER_SENTINEL_PARSER = 'sentinel_parser';
    public const LAYER_GUARDRAIL = 'guardrail';
    public const LAYER_SAFETY_CLASSIFIER = 'safety_classifier';
    public const LAYER_ORCHESTRATOR = 'orchestrator';

    public static function info(string $layer, string $event, array $context = []): void
    {
        self::log('info', $layer, $event, $context);
    }

    // Use for a layer actively rejecting/blocking/falling back - the exact "celah rusak" moments
    // this exists to make traceable, distinct from ordinary info-level activity.
    public static function warning(string $layer, string $event, array $context = []): void
    {
        self::log('warning', $layer, $event, $context);
    }

    public static function error(string $layer, string $event, array $context = []): void
    {
        self::log('error', $layer, $event, $context);
    }

    private static function log(string $level, string $layer, string $event, array $context): void
    {
        Log::channel('chatbot')->log($level, "[{$layer}] {$event}", $context);
    }
}

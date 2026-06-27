<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ChatbotResponseCache
{
    private const CACHE_DURATION = 3600; // 1 hour

    public static function key(string $message, ?string $sessionId = null): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', preg_replace('/[^\pL\pN\s]/u', ' ', mb_strtolower($message))));
        $hash = md5($normalized . ($sessionId ?? ''));
        return "chatbot:response:{$hash}";
    }

    public static function get(string $message, ?string $sessionId = null): ?ChatbotResponse
    {
        return Cache::get(self::key($message, $sessionId));
    }

    public static function put(string $message, ChatbotResponse $response, ?string $sessionId = null): void
    {
        if ($response->statusCode === 200) {
            Cache::put(self::key($message, $sessionId), $response, self::CACHE_DURATION);
        }
    }

    public static function forget(string $message, ?string $sessionId = null): void
    {
        Cache::forget(self::key($message, $sessionId));
    }
}

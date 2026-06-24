<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ChatbotResponseCache
{
    private const CACHE_DURATION = 3600; // 1 hour

    public static function key(string $message): string
    {
        $normalized = Str::lower(trim($message));
        $hash = md5($normalized);
        return "chatbot:response:{$hash}";
    }

    public static function get(string $message): ?ChatbotResponse
    {
        return Cache::get(self::key($message));
    }

    public static function put(string $message, ChatbotResponse $response): void
    {
        if ($response->statusCode === 200) {
            Cache::put(self::key($message), $response, self::CACHE_DURATION);
        }
    }

    public static function forget(string $message): void
    {
        Cache::forget(self::key($message));
    }
}

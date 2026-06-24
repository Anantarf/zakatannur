<?php

namespace App\Services\Chatbot;

class ChatbotSentimentDetector
{
    private const FRUSTRATED_KEYWORDS = [
        'tidak bisa', 'error', 'gagal', 'kenapa', 'kenape', 'gak bisa',
        'masa', 'ndak bisa', 'kok', 'mana', 'mbok', 'bodo', 'bingung sekali',
        'ngasal', 'salah', 'broken', 'not working', 'error', 'failed',
        'why', 'why not', 'useless', 'stupid', 'sucks',
    ];

    private const CONFUSED_KEYWORDS = [
        'bagaimana', 'gimana', 'apa itu', 'maksudnya', 'bingung',
        'gimana cara', 'caranya', 'bagaimana cara', 'apa bedanya',
        'how to', 'how do', 'what is', 'what does', 'confused',
        'don\'t understand', 'unclear', 'tidak paham', 'tidak mengerti',
    ];

    public static function detect(string $message): string
    {
        $lower = strtolower($message);

        $frustratedCount = 0;
        foreach (self::FRUSTRATED_KEYWORDS as $keyword) {
            if (str_contains($lower, $keyword)) {
                $frustratedCount++;
            }
        }

        $confusedCount = 0;
        foreach (self::CONFUSED_KEYWORDS as $keyword) {
            if (str_contains($lower, $keyword)) {
                $confusedCount++;
            }
        }

        if ($frustratedCount > 0) {
            return 'frustrated';
        }

        if ($confusedCount > 0) {
            return 'confused';
        }

        return 'neutral';
    }
}

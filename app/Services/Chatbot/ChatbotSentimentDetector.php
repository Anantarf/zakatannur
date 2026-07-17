<?php

namespace App\Services\Chatbot;

class ChatbotSentimentDetector
{
    public function detect(string $message): string
    {
        $lower = strtolower($message);

        // "kok"/"mana"/"salah"/"kenapa"/"masa"/"why" were dropped - they're everyday Indonesian/
        // English question words that show up constantly in neutral questions ("mana yang lebih
        // murah?", "kenapa nisab beda-beda?"), so they were flagging normal curiosity as
        // frustration and skewing the reply tone for no reason.
        $frustratedWords = [
            'tidak bisa', 'error', 'gagal', 'gak bisa',
            'ndak bisa', 'mbok', 'bodo', 'bingung sekali',
            'ngasal', 'broken', 'not working', 'failed',
            'why not', 'useless', 'stupid', 'sucks',
        ];

        $confusedWords = [
            'bagaimana', 'gimana', 'apa itu', 'maksudnya', 'bingung',
            'gimana cara', 'caranya', 'bagaimana cara', 'apa bedanya',
            'how to', 'how do', 'what is', 'what does', 'confused',
            'don\'t understand', 'unclear', 'tidak paham', 'tidak mengerti',
        ];

        foreach ($frustratedWords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return 'frustrated';
            }
        }

        foreach ($confusedWords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return 'confused';
            }
        }

        return 'neutral';
    }

    public function isCorrectingPreviousNumber(string $message): bool
    {
        $lower = strtolower($message);
        $correctionWords = ['bukan', 'salah', 'harusnya', 'koreksi', 'maksudnya', 'eh', 'ralat', 'seharusnya'];

        foreach ($correctionWords as $word) {
            if (str_contains($lower, $word)) {
                return (bool) preg_match('/\d/', $message);
            }
        }

        return false;
    }
}

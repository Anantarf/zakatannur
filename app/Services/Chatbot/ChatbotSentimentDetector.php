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

        // Whole-word matching (not str_contains substring) + a proximity window around any digit,
        // not "any correction word anywhere and any digit anywhere in the whole message". The old
        // substring check matched 'eh' inside "boleh"/"oleh" - both extremely common Indonesian
        // words - so "Apakah boleh saya bayar zakat fitrah untuk 4 orang sekaligus?" (an ordinary
        // first-time question, not a correction) triggered this. Whole-word matching alone doesn't
        // fix 'salah' inside the equally common phrase "salah satu" ("one of"), so that phrase is
        // excluded explicitly.
        $words = preg_split('/\s+/', $lower, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $wordCount = count($words);
        $proximityWindow = 6;

        foreach ($words as $index => $rawWord) {
            $word = trim($rawWord, ".,!?;:()\"'");
            if (!in_array($word, $correctionWords, true)) {
                continue;
            }

            if ($word === 'salah' && ($words[$index + 1] ?? '') === 'satu') {
                continue;
            }

            $start = max(0, $index - $proximityWindow);
            $end = min($wordCount - 1, $index + $proximityWindow);
            for ($i = $start; $i <= $end; $i++) {
                if (preg_match('/\d/', $words[$i])) {
                    return true;
                }
            }
        }

        return false;
    }
}

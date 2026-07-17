<?php

namespace App\Services\Chatbot;

class ChatbotLanguageDetector
{
    public function detect(string $message): string
    {
        $lower = strtolower($message);
        $englishWords = [
            'how', 'what', 'when', 'where', 'why', 'who', 'is', 'are', 'do', 'does',
            'can', 'should', 'would', 'could', 'will', 'have', 'has', 'been', 'total',
            'collected', 'much', 'many', 'money', 'rice', 'pay', 'help', 'information',
            'please', 'thank', 'hello', 'hi', 'yes', 'no', 'tell', 'show', 'give',
            'chart', 'graph', 'data', 'report', 'summary', 'account', 'transaction',
            'history', 'status', 'update', 'latest', 'current', 'recent', 'check',
        ];

        $englishCount = 0;
        foreach ($englishWords as $word) {
            if (preg_match('/\b' . preg_quote($word) . '\b/', $lower)) {
                $englishCount++;
            }
        }

        $wordCount = count(array_filter(str_word_count($lower, 1)));
        $englishRatio = $wordCount > 0 ? $englishCount / $wordCount : 0;

        return $englishRatio > 0.3 ? 'en' : 'id';
    }
}

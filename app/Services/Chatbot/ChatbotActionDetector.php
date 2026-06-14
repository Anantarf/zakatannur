<?php

namespace App\Services\Chatbot;

class ChatbotActionDetector
{
    public function detect(string $message): ?ChatbotResponse
    {
        $message = $this->normalize($message);

        if ($this->containsAny($message, ['berapa', 'totalnya', 'jumlahnya'])) {
            return null;
        }

        if ($this->containsAny($message, ['ringkasan', 'penerimaan', 'total zakat', 'data zakat', 'laporan'])) {
            return ChatbotResponse::success(
                'Saya buka tab Ringkasan Penerimaan. Di sana jamaah bisa melihat total jiwa, uang, beras, dan rincian kategori.',
                'action',
                [['type' => 'open_tab', 'target' => 'laporan']]
            );
        }

        if ($this->containsAny($message, ['grafik', 'harian', 'chart', 'tren', 'pola penerimaan'])) {
            return ChatbotResponse::success(
                'Saya buka tab Grafik Harian. Di sana jamaah bisa melihat pola penerimaan per hari.',
                'action',
                [['type' => 'open_tab', 'target' => 'grafik']]
            );
        }

        return null;
    }

    private function normalize(string $message): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtolower($message)) ?? '');
    }

    private function containsAny(string $message, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }
}

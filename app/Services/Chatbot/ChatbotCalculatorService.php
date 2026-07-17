<?php

namespace App\Services\Chatbot;

class ChatbotCalculatorService
{
    public function calculateFitrah(string $message): ChatbotResponse
    {
        $count = $this->extractNumberFromText($message, ['orang', 'jiwa', 'person']);
        
        if (!$count) {
            return ChatbotResponse::success(
                'Berapa orang yang mau dihitung fitrahnya? Coba ketik: "Fitrah 4 orang berapa?"',
                'knowledge'
            );
        }
        $cashPerJiwa = config('zakat.annual_defaults.fitrah_cash_per_jiwa', 50000);
        $berasPerJiwa = config('zakat.annual_defaults.fitrah_beras_per_jiwa', 2.5);

        $totalCash = $count * $cashPerJiwa;
        $totalBeras = $count * $berasPerJiwa;

        $reply = sprintf(
            "Fitrah untuk %d orang:\n\n"
            . "Uang  : %d x Rp %s = Rp %s\n"
            . "Beras : %d x %.1f kg = %.1f kg\n\n"
            . "Angka ini mengacu tarif An-Nur tahun ini. Konfirmasi ke panitia sebelum bayar ya.",
            $count,
            $count, number_format($cashPerJiwa, 0, ',', '.'), number_format($totalCash, 0, ',', '.'),
            $count, $berasPerJiwa, $totalBeras
        );

        return ChatbotResponse::success($reply, 'calculation');
    }

    public function calculateFidyah(string $message): ChatbotResponse
    {
        $days = $this->extractNumberFromText($message, ['hari', 'day']);
        
        if (!$days) {
            return ChatbotResponse::success(
                'Berapa hari fidyahnya? Coba ketik: "Fidyah 7 hari berapa?"',
                'knowledge'
            );
        }
        $cashPerHari = config('zakat.annual_defaults.fidyah_per_hari', 30000);
        $berasPerHari = config('zakat.annual_defaults.fidyah_beras_per_hari', 0.75);

        $totalCash = $days * $cashPerHari;
        $totalBeras = $days * $berasPerHari;

        $reply = sprintf(
            "Fidyah untuk %d hari:\n\n"
            . "Uang  : %d x Rp %s = Rp %s\n"
            . "Beras : %d x %.2f kg = %.2f kg\n\n"
            . "Angka ini mengacu tarif An-Nur tahun ini. Konfirmasi ke panitia sebelum bayar ya.",
            $days,
            $days, number_format($cashPerHari, 0, ',', '.'), number_format($totalCash, 0, ',', '.'),
            $days, $berasPerHari, $totalBeras
        );

        return ChatbotResponse::success($reply, 'calculation');
    }

    private function extractNumberFromText(string $text, array $keywords): ?int
    {
        $normalized = strtolower($text);
        
        // 1. Try to find a digit near the keyword
        foreach ($keywords as $keyword) {
            if (preg_match('/(\d+)[\s]*' . preg_quote($keyword) . '/i', $normalized, $matches)) {
                return (int) $matches[1];
            }
        }
        
        // 2. Try to map words to numbers near keyword
        $map = [
            'satu' => 1, 'dua' => 2, 'tiga' => 3, 'empat' => 4, 'lima' => 5,
            'enam' => 6, 'tujuh' => 7, 'delapan' => 8, 'sembilan' => 9, 'sepuluh' => 10,
            'sebelas' => 11, 'dua belas' => 12
        ];
        
        foreach ($keywords as $keyword) {
            foreach ($map as $word => $num) {
                if (preg_match('/' . preg_quote($word) . '[\s]*' . preg_quote($keyword) . '/i', $normalized)) {
                    return $num;
                }
            }
        }
        
        // 3. Fallback: just try to find any number in the message
        if (preg_match('/(\d+)/', $normalized, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}

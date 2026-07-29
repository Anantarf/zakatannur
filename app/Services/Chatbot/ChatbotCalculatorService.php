<?php

namespace App\Services\Chatbot;

class ChatbotCalculatorService
{
    // A sanity ceiling on extracted counts - a single fitrah/fidyah query realistically never
    // needs more than a few hundred people/days at once. This is what stands between "grabbed the
    // wrong number" and a wrong-but-plausible-looking result reaching the user undetected (the
    // most dangerous failure mode: no error, no clarification, just a confident wrong answer).
    private const MAX_PLAUSIBLE_COUNT = 1000;

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

        // 1. Digit near the keyword - immediately before it ("4 orang") or within a couple filler
        // words after it ("orangnya ada 4", "orang sebanyak 4"). Proximity to the keyword (not
        // "any number anywhere in the message") is what makes this reliable - a blind "grab the
        // first number in the message" fallback used to sit at the end of this method and would
        // happily grab an unrelated year: "Fitrah tahun 2026 itu berapa ya per orang?" (asking
        // about this year's rate, not requesting a calculation) silently became "2026 orang" ->
        // Rp101.300.000, a wrong answer with no error and no clarification asked.
        foreach ($keywords as $keyword) {
            $quotedKeyword = preg_quote($keyword, '/');
            $matched = preg_match('/(\d+)\s*' . $quotedKeyword . '/i', $normalized, $matches)
                || preg_match('/' . $quotedKeyword . '(?:\s+\w+){0,2}?\s+(\d+)\b/i', $normalized, $matches);

            if ($matched) {
                $count = (int) $matches[1];
                if ($count > 0 && $count <= self::MAX_PLAUSIBLE_COUNT) {
                    return $count;
                }
                // A number was found right next to the keyword but it's implausible (e.g. a year
                // slipped in as "4 orang" would never do) - don't fall through to the word-number
                // map or treat this as "no number found"; ask the user to restate instead.
                return null;
            }
        }

        // 2. Word-numbers near the keyword ("empat orang").
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

        return null;
    }
}

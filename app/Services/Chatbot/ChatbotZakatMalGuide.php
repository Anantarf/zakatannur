<?php

namespace App\Services\Chatbot;

class ChatbotZakatMalGuide
{
    // ponytail: regex extraction only, no NLP. Multi-language support if needed later
    public function detect(string $message): ?array
    {
        $message = $this->normalize($message);
        
        // Check if message looks like zakat mal scenario (has income/asset keywords + zakat question)
        if (!$this->hasZakatQuestion($message) || !$this->hasFinancialKeywords($message)) {
            return null;
        }

        return [
            'income_monthly' => $this->extractNumber($message, ['gaji', 'penghasilan', 'pendapatan bulanan', 'bulan']),
            'expenses_monthly' => $this->extractNumber($message, ['pengeluaran', 'biaya', 'kebutuhan', 'rutin']),
            'savings' => $this->extractNumber($message, ['tabungan', 'simpanan', 'uang cash', 'cash']),
            'gold_gram' => $this->extractNumber($message, ['emas', 'gram', 'gr']),
            'debt' => $this->extractNumber($message, ['hutang', 'utang']),
        ];
    }

    public function calculate(array $data): array
    {
        $income = $data['income_monthly'] ?? 0;
        $expenses = $data['expenses_monthly'] ?? 0;
        $savings = $data['savings'] ?? 0;
        $goldGram = $data['gold_gram'] ?? 0;
        $debt = $data['debt'] ?? 0;

        // Annual calculation
        $annualIncome = $income * 12;
        $annualExpenses = $expenses * 12;
        $goldValue = $goldGram * 900000; // ponytail: fixed rate, use live rate if needed

        $totalAssets = $annualIncome + $savings + $goldValue;
        $nettAssets = $totalAssets - $annualExpenses - $debt;

        $nishab = 65000000; // ~85g gold, conservative lower bound
        $isAboveNishab = $nettAssets >= $nishab;

        $zakatAmount = $isAboveNishab ? (int) ($nettAssets * 0.025) : 0;

        return [
            'annual_income' => $annualIncome,
            'annual_expenses' => $annualExpenses,
            'gold_value' => $goldValue,
            'total_assets' => $totalAssets,
            'debt' => $debt,
            'nett_assets' => $nettAssets,
            'nishab' => $nishab,
            'is_above_nishab' => $isAboveNishab,
            'zakat_amount' => $zakatAmount,
        ];
    }

    private function hasZakatQuestion(string $message): bool
    {
        return $this->containsAny($message, ['zakat berapa', 'berapa zakat', 'hitung zakat', 'zakat brp']);
    }

    private function hasFinancialKeywords(string $message): bool
    {
        $hasSome = $this->containsAny($message, [
            'gaji', 'penghasilan', 'pendapatan', 'tabungan', 'emas', 'properti', 
            'hutang', 'pengeluaran', 'aset', 'harta'
        ]);
        return $hasSome;
    }

    private function extractNumber(string $message, array $keywords): ?int
    {
        foreach ($keywords as $keyword) {
            if (preg_match('/' . preg_quote($keyword) . '.*?([\d\.,]+[\s]*(?:juta|ribu|rb|jt|miliar)?)/i', $message, $matches)) {
                $numStr = strtolower(trim($matches[1]));
                
                $multiplier = 1;
                if (str_contains($numStr, 'miliar')) {
                    $multiplier = 1000000000;
                } elseif (str_contains($numStr, 'juta') || str_contains($numStr, 'jt')) {
                    $multiplier = 1000000;
                } elseif (str_contains($numStr, 'ribu') || str_contains($numStr, 'rb')) {
                    $multiplier = 1000;
                }
                
                $rawNumber = preg_replace('/[^\d\.,]/', '', $numStr);
                
                if ($multiplier > 1 && preg_match('/^(\d+)[\.,](\d+)$/', $rawNumber, $parts)) {
                    $floatVal = (float) ($parts[1] . '.' . $parts[2]);
                    return (int) ($floatVal * $multiplier);
                }
                
                $cleanNum = (int) preg_replace('/[^\d]/', '', $rawNumber);
                
                return ($cleanNum * $multiplier) ?: null;
            }
        }
        return null;
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

    private function normalize(string $message): string
    {
        $message = preg_replace('/[^\pL\pN\s\.,]/u', ' ', mb_strtolower($message)) ?? '';
        return trim(preg_replace('/\s+/', ' ', $message) ?? '');
    }
}

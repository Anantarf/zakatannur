<?php

namespace App\Services\Chatbot;

class ChatbotZakatMalGuide
{
    /**
     * Income (arus pendapatan) and wealth (tabungan/emas, harta tersimpan) are assessed against
     * nisab separately, then combined only at the zakat-amount level. They are NOT pooled into
     * one asset base (gross annual income + savings) - savings normally already reflects income
     * earned and spent throughout the year, so summing them with a full year of gross income
     * double-counts it and overstates the zakat owed.
     */
    public function calculate(array $data, \App\Services\Transactions\AnnualZakatDefaults $defaults): array
    {
        $incomeMonthly = (int) ($data['income_monthly'] ?? 0);
        $savings = (int) ($data['savings'] ?? 0);
        $goldGram = (int) ($data['gold_gram'] ?? 0);
        $debt = (int) ($data['debt'] ?? 0);

        $goldValue = $goldGram * $defaults->goldPricePerGram;
        $nishab = $defaults->nishabAnnual();

        // 1. Zakat penghasilan - basis dari penghasilan bruto (gaji pokok + tunjangan), TANPA
        // dikurangi kebutuhan pokok/pengeluaran rutin - mengikuti SK Ketua BAZNAS No. 15/2026.
        $incomeAnnual = $incomeMonthly * 12;
        $incomeIsDue = $incomeAnnual >= $nishab;
        $incomeZakat = $incomeIsDue ? (int) ($incomeAnnual * 0.025) : 0;

        // 2. Zakat tabungan & emas - basis dari harta simpanan saat ini, dikurangi hutang.
        $wealthBase = max(0, $savings + $goldValue - $debt);
        $wealthIsDue = $wealthBase >= $nishab;
        $wealthZakat = $wealthIsDue ? (int) ($wealthBase * 0.025) : 0;

        return [
            'nishab' => $nishab,
            'income_annual' => $incomeAnnual,
            'income_is_due' => $incomeIsDue,
            'income_zakat' => $incomeZakat,
            'gold_value' => $goldValue,
            'wealth_base' => $wealthBase,
            'wealth_is_due' => $wealthIsDue,
            'wealth_zakat' => $wealthZakat,
            'total_zakat' => $incomeZakat + $wealthZakat,
        ];
    }
}

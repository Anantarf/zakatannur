<?php

namespace App\Services\Chatbot;

class ChatbotZakatMalGuide
{
    public function calculate(array $data, \App\Services\Transactions\AnnualZakatDefaults $defaults): array
    {
        $income = (int) ($data['income_monthly'] ?? 0);
        $expenses = (int) ($data['expenses_monthly'] ?? 0);
        $savings = (int) ($data['savings'] ?? 0);
        $goldGram = (int) ($data['gold_gram'] ?? 0);
        $debt = (int) ($data['debt'] ?? 0);

        // Annual calculation
        $annualIncome = $income * 12;
        $annualExpenses = $expenses * 12;
        
        $goldPrice = $defaults->goldPricePerGram;
        $goldValue = $goldGram * $goldPrice;

        $totalAssets = $annualIncome + $savings + $goldValue;
        $nettAssets = $totalAssets - $annualExpenses - $debt;

        $nishab = $defaults->nishabGoldGram * $goldPrice;
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
}

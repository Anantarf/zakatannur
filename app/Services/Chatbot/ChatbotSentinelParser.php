<?php

namespace App\Services\Chatbot;

class ChatbotSentinelParser
{
    public function parseAndCalculateSentinel(string $reply): string
    {
        if (preg_match('/\[HITUNG:\s*(\{.*?\})\s*\]/is', $reply, $matches)) {
            $jsonStr = $matches[1];
            $data = json_decode($jsonStr, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                // Rusak
                $replacement = "\n\n(Mohon maaf, saya kurang mengerti datanya. Bisa sebutkan nominal penghasilan bulanan, tabungan, dan emas yang dimiliki?)";
            } else {
                $year = (int) \App\Models\AppSetting::getInt(\App\Models\AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);
                $defaultsResolver = app(\App\Services\Transactions\AnnualZakatDefaultsResolver::class);
                $defaults = $defaultsResolver->resolve($year);

                $maxPlausibleValue = ($defaults->nishabGoldGram * $defaults->goldPricePerGram) * 1000;

                $hasNegative = false;
                $hasImplausible = false;
                $allEmpty = true;
                foreach (['income_monthly', 'expenses_monthly', 'savings', 'gold_gram', 'debt'] as $key) {
                    if (isset($data[$key])) {
                        $allEmpty = false;
                        $value = (int) $data[$key];
                        if ($value < 0) {
                            $hasNegative = true;
                        } elseif ($value > $maxPlausibleValue) {
                            $hasImplausible = true;
                        }
                    }
                }

                if ($hasNegative) {
                    $replacement = "\n\n(Pastikan nominal yang Anda masukkan tidak kurang dari nol. Mari coba hitung ulang.)";
                } elseif ($hasImplausible) {
                    $replacement = "\n\n(Sepertinya ada nominal yang kurang masuk akal. Mohon sebutkan ulang angkanya, misalnya \"10 juta\" bukan \"10 miliar\".)";
                } elseif ($allEmpty) {
                    $replacement = "\n\n(Bisa sebutkan nominal penghasilan atau tabungannya agar bisa saya hitung?)";
                } else {
                    $guide = app(ChatbotZakatMalGuide::class);
                    $result = $guide->calculate($data, $defaults);

                    $inputSummary = sprintf(
                        "Baik, saya coba hitungkan dari data yang Anda berikan ya:\n"
                        . "- Penghasilan bulanan: Rp %s\n"
                        . "- Pengeluaran rutin bulanan: Rp %s\n"
                        . "- Tabungan: Rp %s\n"
                        . "- Emas: %d gram\n"
                        . "- Hutang: Rp %s\n"
                        . "(Kalau ada yang kurang tepat, tinggal koreksi saja nominalnya.)\n\n",
                        number_format((int) ($data['income_monthly'] ?? 0), 0, ',', '.'),
                        number_format((int) ($data['expenses_monthly'] ?? 0), 0, ',', '.'),
                        number_format((int) ($data['savings'] ?? 0), 0, ',', '.'),
                        (int) ($data['gold_gram'] ?? 0),
                        number_format((int) ($data['debt'] ?? 0), 0, ',', '.')
                    );

                    // Penghasilan dan tabungan/emas dinilai terpisah (lihat ChatbotZakatMalGuide) -
                    // supaya jawabannya tidak menyiratkan satu "aset neto" gabungan yang sebenarnya
                    // sudah menghitung ganda penghasilan yang sama.
                    $incomeLine = $result['income_is_due']
                        ? sprintf(
                            'Kesimpulan: wajib zakat penghasilan, sekitar Rp %s per tahun (~Rp %s per bulan).',
                            number_format($result['income_zakat'], 0, ',', '.'),
                            number_format((int) ($result['income_zakat'] / 12), 0, ',', '.')
                        )
                        : 'Kesimpulan: belum wajib zakat penghasilan saat ini.';

                    $wealthLine = $result['wealth_is_due']
                        ? sprintf(
                            'Kesimpulan: wajib zakat tabungan/emas, sekitar Rp %s per tahun.',
                            number_format($result['wealth_zakat'], 0, ',', '.')
                        )
                        : 'Kesimpulan: belum wajib zakat tabungan/emas saat ini.';

                    // [[HASIL]]...[[/HASIL]] marks the computed answer so the frontend can render
                    // it as its own card instead of plain chat text — a calculated zakat figure
                    // should look distinct from an ordinary FAQ reply.
                    $replacement = "\n\n" . $inputSummary . '[[HASIL]]' . sprintf(
                        "**Estimasi Zakat Penghasilan** (dari penghasilan bersih, terpisah dari tabungan):\n"
                        . "- Penghasilan bersih tahunan: Rp %s\n"
                        . "- Nishab: Rp %s\n"
                        . "%s\n\n"
                        . "**Estimasi Zakat Tabungan & Emas** (dari harta simpanan saat ini):\n"
                        . "- Aset simpanan (tabungan + emas - hutang): Rp %s\n"
                        . "- Nishab: Rp %s\n"
                        . "%s\n\n"
                        . "**Total estimasi zakat: Rp %s per tahun.**",
                        number_format($result['net_income_annual'], 0, ',', '.'),
                        number_format($result['nishab'], 0, ',', '.'),
                        $incomeLine,
                        number_format($result['wealth_base'], 0, ',', '.'),
                        number_format($result['nishab'], 0, ',', '.'),
                        $wealthLine,
                        number_format($result['total_zakat'], 0, ',', '.')
                    ) . '[[/HASIL]]';
                }
            }

            $reply = trim(str_replace($matches[0], $replacement, $reply));
        }

        return $reply;
    }
}

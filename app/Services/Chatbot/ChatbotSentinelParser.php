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

                // Income and wealth are asked about (and shown) as independent sections - if the
                // user only ever discussed income, showing a "belum wajib zakat tabungan/emas"
                // wealth section implies their savings/gold were actually assessed and came up
                // empty, when really those fields were never mentioned at all (defaulted to 0).
                // income_monthly specifically (not just expenses_monthly) gates the income section -
                // expenses alone still computes net income as Rp0 (max(0, 0 - expenses)), which
                // would show the same "never discussed, defaulted to zero" problem this fixes.
                $hasIncomeData = isset($data['income_monthly']);
                $hasWealthData = isset($data['savings']) || isset($data['gold_gram']) || isset($data['debt']);

                if ($hasNegative) {
                    $replacement = "\n\n(Pastikan nominal yang Anda masukkan tidak kurang dari nol. Mari coba hitung ulang.)";
                } elseif ($hasImplausible) {
                    $replacement = "\n\n(Sepertinya ada nominal yang kurang masuk akal. Mohon sebutkan ulang angkanya, misalnya \"10 juta\" bukan \"10 miliar\".)";
                } elseif ($allEmpty || (!$hasIncomeData && !$hasWealthData)) {
                    // The second condition catches e.g. expenses_monthly given without
                    // income_monthly - not "empty" (a key was set), but not enough to anchor
                    // either section, which would otherwise render an empty [[HASIL]] block.
                    $replacement = "\n\n(Bisa sebutkan nominal penghasilan atau tabungannya agar bisa saya hitung?)";
                } else {
                    $guide = app(ChatbotZakatMalGuide::class);
                    $result = $guide->calculate($data, $defaults);

                    // Only list the fields the user actually mentioned - listing a defaulted "Rp 0"
                    // for a field never discussed (e.g. "Tabungan: Rp 0" when tabungan was never
                    // brought up) misleadingly implies the user said they have none.
                    $summaryLines = [];
                    if (isset($data['income_monthly'])) {
                        $summaryLines[] = sprintf('- Penghasilan bulanan: Rp %s', number_format((int) $data['income_monthly'], 0, ',', '.'));
                    }
                    if (isset($data['expenses_monthly'])) {
                        $summaryLines[] = sprintf('- Pengeluaran rutin bulanan: Rp %s', number_format((int) $data['expenses_monthly'], 0, ',', '.'));
                    }
                    if (isset($data['savings'])) {
                        $summaryLines[] = sprintf('- Tabungan: Rp %s', number_format((int) $data['savings'], 0, ',', '.'));
                    }
                    if (isset($data['gold_gram'])) {
                        $summaryLines[] = sprintf('- Emas: %d gram', (int) $data['gold_gram']);
                    }
                    if (isset($data['debt'])) {
                        $summaryLines[] = sprintf('- Hutang: Rp %s', number_format((int) $data['debt'], 0, ',', '.'));
                    }

                    $inputSummary = "Baik, saya coba hitungkan dari data yang Anda berikan ya:\n"
                        . implode("\n", $summaryLines) . "\n"
                        . "(Kalau ada yang kurang tepat, tinggal koreksi saja nominalnya.)\n\n";

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

                    $sections = [];
                    if ($hasIncomeData) {
                        $sections[] = sprintf(
                            "**Estimasi Zakat Penghasilan** (dari penghasilan bersih, terpisah dari tabungan):\n"
                            . "- Penghasilan bersih tahunan: Rp %s\n"
                            . "- Nishab: Rp %s\n"
                            . "%s",
                            number_format($result['net_income_annual'], 0, ',', '.'),
                            number_format($result['nishab'], 0, ',', '.'),
                            $incomeLine
                        );
                    }
                    if ($hasWealthData) {
                        $sections[] = sprintf(
                            "**Estimasi Zakat Tabungan & Emas** (dari harta simpanan saat ini):\n"
                            . "- Aset simpanan (tabungan + emas - hutang): Rp %s\n"
                            . "- Nishab: Rp %s\n"
                            . "%s",
                            number_format($result['wealth_base'], 0, ',', '.'),
                            number_format($result['nishab'], 0, ',', '.'),
                            $wealthLine
                        );
                    }
                    // Total line only makes sense to show once both categories relevant to the
                    // user have actually been assessed - with just one section it would just
                    // repeat that section's own number under a different label.
                    if ($hasIncomeData && $hasWealthData) {
                        $sections[] = sprintf('**Total estimasi zakat: Rp %s per tahun.**', number_format($result['total_zakat'], 0, ',', '.'));
                    }

                    // [[HASIL]]...[[/HASIL]] marks the computed answer so the frontend can render
                    // it as its own card instead of plain chat text — a calculated zakat figure
                    // should look distinct from an ordinary FAQ reply.
                    $replacement = "\n\n" . $inputSummary . '[[HASIL]]' . implode("\n\n", $sections) . '[[/HASIL]]';
                }
            }

            $reply = trim(str_replace($matches[0], $replacement, $reply));
        }

        return $reply;
    }
}

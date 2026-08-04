<?php

namespace App\Services\Chatbot;

class ChatbotSentinelParser
{
    public function parseAndCalculateSentinel(string $reply): string
    {
        // preg_replace_callback (not preg_match + str_replace) so that IF the LLM ever emits more
        // than one [HITUNG:...] tag in a single reply (not the intended usage, but not something
        // the prompt can fully rule out), every tag gets computed and replaced independently -
        // not just the first, leaving any later tag's raw JSON syntax leaking to the user.
        $reply = preg_replace_callback('/\[HITUNG:\s*(\{.*?\})\s*\]/is', function (array $matches) {
            return $this->calculateSentinel($matches[1]);
        }, $reply);

        return trim($reply);
    }

    private function calculateSentinel(string $jsonStr): string
    {
        $data = json_decode($jsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            // Rusak - almost always means the LLM emitted a sentinel that doesn't match the
            // schema in its own system prompt, worth knowing if it happens often.
            ChatbotDiagnostics::warning(ChatbotDiagnostics::LAYER_SENTINEL_PARSER, 'malformed_json', [
                'json_error' => json_last_error_msg(),
            ]);

            return "\n\n(Mohon maaf, saya kurang mengerti datanya. Bisa sebutkan nominal penghasilan bulanan, tabungan, dan emas yang dimiliki?)";
        }

        $year = (int) \App\Models\AppSetting::getInt(\App\Models\AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);
        $defaultsResolver = app(\App\Services\Transactions\AnnualZakatDefaultsResolver::class);
        $defaults = $defaultsResolver->resolve($year);

        $maxPlausibleValue = $defaults->nishabAnnual() * 1000;

        $hasNegative = false;
        $hasImplausible = false;
        $hasNonNumeric = false;
        $allEmpty = true;
        foreach (['income_monthly', 'savings', 'gold_gram', 'debt'] as $key) {
            if (isset($data[$key])) {
                $allEmpty = false;
                // is_numeric(), not a bare (int) cast: PHP's (int) cast on a string stops at the
                // first invalid character rather than rejecting it, so a formatted number like
                // "10.000.000" (Rupiah thousands-separator style - the exact style this bot uses
                // in its own replies, so an LLM slip into that format here is plausible) silently
                // becomes (int) 10, not 10 million. Silently computing "belum wajib zakat" off a
                // truncated value is worse than the other failure modes here - it looks correct.
                if (!is_numeric($data[$key])) {
                    $hasNonNumeric = true;
                    continue;
                }
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
        // debt is deliberately NOT part of this check - debt only modifies a savings/gold
        // base, it isn't itself evidence the user discussed tabungan/emas. Debt-only input
        // (e.g. "saya punya hutang 5 juta") would otherwise still light up the wealth
        // section with the same "assessed and came up empty" problem this guards against.
        $hasIncomeData = isset($data['income_monthly']);
        $hasWealthData = isset($data['savings']) || isset($data['gold_gram']);

        if ($hasNonNumeric) {
            ChatbotDiagnostics::warning(ChatbotDiagnostics::LAYER_SENTINEL_PARSER, 'rejected_non_numeric_value', ['data' => $data]);
            return "\n\n(Mohon maaf, saya kurang mengerti datanya. Bisa sebutkan nominal penghasilan bulanan, tabungan, dan emas yang dimiliki?)";
        }

        if ($hasNegative) {
            ChatbotDiagnostics::warning(ChatbotDiagnostics::LAYER_SENTINEL_PARSER, 'rejected_negative_value', ['data' => $data]);
            return "\n\n(Pastikan nominal yang Anda masukkan tidak kurang dari nol. Mari coba hitung ulang.)";
        }

        if ($hasImplausible) {
            ChatbotDiagnostics::warning(ChatbotDiagnostics::LAYER_SENTINEL_PARSER, 'rejected_implausible_value', ['data' => $data]);
            return "\n\n(Sepertinya ada nominal yang kurang masuk akal. Mohon sebutkan ulang angkanya, misalnya \"10 juta\" bukan \"10 miliar\".)";
        }

        if ($allEmpty || (!$hasIncomeData && !$hasWealthData)) {
            // The second condition catches e.g. debt given alone (or alongside a stray
            // key) without income/savings/gold - not "empty" (a key was set), but not
            // enough to anchor either section, which would otherwise render an empty
            // [[HASIL]] block.
            ChatbotDiagnostics::info(ChatbotDiagnostics::LAYER_SENTINEL_PARSER, 'insufficient_data_to_anchor_a_section', ['data' => $data]);
            return "\n\n(Bisa sebutkan nominal penghasilan atau tabungannya agar bisa saya hitung?)";
        }

        $guide = app(ChatbotZakatMalGuide::class);
        $result = $guide->calculate($data, $defaults);

        // Only list the fields the user actually mentioned - listing a defaulted "Rp 0"
        // for a field never discussed (e.g. "Tabungan: Rp 0" when tabungan was never
        // brought up) misleadingly implies the user said they have none.
        $summaryLines = [];
        if (isset($data['income_monthly'])) {
            $summaryLines[] = sprintf('- Penghasilan bulanan: Rp %s', number_format((int) $data['income_monthly'], 0, ',', '.'));
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
                "**Estimasi Zakat Penghasilan** (dari penghasilan bruto, terpisah dari tabungan):\n"
                . "- Penghasilan tahunan: Rp %s\n"
                . "- Nishab: Rp %s\n"
                . "%s",
                number_format($result['income_annual'], 0, ',', '.'),
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
        return "\n\n" . $inputSummary . '[[HASIL]]' . implode("\n\n", $sections) . '[[/HASIL]]';
    }
}

<?php

namespace App\Services\Chatbot;

// Reads/derives conversation state (mode, hints, cache key) carried across turns via the
// frontend-roundtripped context blob, and injects prompt hints into the RAG contexts sent
// to the AI provider.
class ChatbotConversationContext
{
    public function __construct(private ChatbotSentimentDetector $sentimentDetector)
    {
    }

    public function parse(array $rawContext): array
    {
        return [
            'last_intent' => is_string($rawContext['last_intent'] ?? null) ? trim($rawContext['last_intent']) : null,
            'last_source' => is_string($rawContext['last_source'] ?? null) ? trim($rawContext['last_source']) : null,
            'topic' => is_string($rawContext['topic'] ?? null) ? trim($rawContext['topic']) : null,
            'mode' => is_string($rawContext['mode'] ?? null) ? trim($rawContext['mode']) : null,
        ];
    }

    public function forIntent(string $intent, string $source): array
    {
        $topic = 'general';
        if ($source === 'public_data' || str_starts_with($intent, 'ask_')) {
            $topic = 'public_data';
        } elseif ($source === 'knowledge') {
            $topic = 'knowledge';
        } elseif ($source === 'action') {
            $topic = 'navigation';
        }

        return array_filter([
            'last_intent' => $intent,
            'last_source' => $source,
            'topic' => $topic,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function cacheKey(string $message, array $rawContext, ?string $sessionId): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', preg_replace('/[^\pL\pN\s]/u', ' ', mb_strtolower($message))));
        $context = $this->parse($rawContext);
        $contextPart = implode('|', [
            $context['last_intent'] ?? '',
            $context['last_source'] ?? '',
            $context['topic'] ?? '',
            $context['mode'] ?? '',
        ]);
        $hash = md5($normalized . '|' . $contextPart . '|' . ($sessionId ?? ''));
        return "chatbot:response:{$hash}";
    }

    public function detectMode(string $message, array $rawContext): string
    {
        $context = $this->parse($rawContext);
        $normalized = mb_strtolower($message);

        $hasZakatMal = str_contains($normalized, 'zakat mal')
            || str_contains($normalized, 'zakat maal')
            || str_contains($normalized, 'hitung zakat')
            || str_contains($normalized, 'nisab')
            || str_contains($normalized, 'nishab');

        // A bare "any digit" check used to sit here - it fired on literally any message with a
        // number in it ("jadwal shalat jam 5 sore", "nomor antrian saya 15"), pushing totally
        // unrelated questions into zakat_mal_consultation mode (injecting the consultation hint
        // into the system prompt, and sticking there via the mode round-trip below). Digits paired
        // with an actual financial keyword are already covered by the checks below; a bare numeric
        // follow-up mid-consultation ("10 juta") is handled by the "stay in mode" branch further
        // down, which doesn't need this signal at all - it keys off the previous turn's mode.
        // Kept in sync with ChatbotActionDetector's sibling $looksLikeCalculationRequest word list -
        // "penghasilan" was missing here even though it's listed there, so an income question
        // phrased with "penghasilan" instead of "gaji" fell through to 'general' mode and never got
        // the _conversation_hint that tells the AI to guide the calculation via [HITUNG:...].
        $hasFinancialSignal = str_contains($normalized, 'gaji')
            || str_contains($normalized, 'penghasilan')
            || str_contains($normalized, 'tabungan')
            || str_contains($normalized, 'emas')
            || str_contains($normalized, 'hutang')
            || str_contains($normalized, 'pengeluaran')
            || str_contains($normalized, 'aset');

        if (($context['mode'] ?? null) === 'zakat_mal_consultation') {
            // Stay for short/ambiguous follow-ups (bare numbers, "tidak ada hutang", "iya") since
            // those carry no topic keyword of their own. But let an explicit switch to another
            // zakat topic actually leave the mode - otherwise a user asking about zakat fitrah or
            // the payment schedule mid-consultation gets stuck being asked for more zakat mal data
            // instead of an answer to what they just asked.
            $switchesTopic = str_contains($normalized, 'zakat fitrah')
                || str_contains($normalized, 'fidyah')
                || str_contains($normalized, 'jadwal')
                || str_contains($normalized, 'dashboard')
                || str_contains($normalized, 'grafik')
                || str_contains($normalized, 'infaq')
                || str_contains($normalized, 'shodaqoh')
                || str_contains($normalized, 'cara bayar')
                || str_contains($normalized, 'konfirmasi');

            if (!$switchesTopic || $hasZakatMal || $hasFinancialSignal) {
                return 'zakat_mal_consultation';
            }
        }

        return $hasZakatMal || $hasFinancialSignal ? 'zakat_mal_consultation' : 'general';
    }

    public function aiContext(string $mode): array
    {
        return array_filter([
            'last_source' => 'ai',
            'topic' => $mode === 'zakat_mal_consultation' ? 'zakat_mal' : 'ai_conversation',
            'mode' => $mode,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function withHints(array $contexts, string $message, string $sentiment, string $mode): array
    {
        $contexts = $this->applySentimentHint($contexts, $sentiment);
        $contexts = $this->applyCorrectionHint($contexts, $message);
        $contexts = $this->applyConversationHint($contexts, $mode);

        return $contexts;
    }

    private function applySentimentHint(array $contexts, string $sentiment): array
    {
        if ($sentiment === 'frustrated') {
            $contexts = $this->mergeHintIntoContexts($contexts, [
                '_sentiment_hint' => 'User appears frustrated. Be empathetic, concise, and offer clear next steps.',
            ]);
        }

        return $contexts;
    }

    private function applyCorrectionHint(array $contexts, string $message): array
    {
        if ($this->sentimentDetector->isCorrectingPreviousNumber($message)) {
            $contexts = $this->mergeHintIntoContexts($contexts, [
                '_correction_hint' => 'User tampaknya sedang mengoreksi angka yang sudah disebut sebelumnya. '
                    . 'GANTI nilai lama itu dengan nilai baru, jangan menjumlahkan keduanya.',
            ]);
        }

        return $contexts;
    }

    private function applyConversationHint(array $contexts, string $mode): array
    {
        if ($mode !== 'zakat_mal_consultation') {
            return $contexts;
        }

        // Grounds the LLM with the real configured nisab instead of relying on it obeying a
        // "don't invent a figure" prohibition - retrieval is skipped for most turns in this mode
        // (see ChatbotOrchestrator::retrieveContexts), so without this the model's only source
        // for a nisab rupiah figure is its own (often stale) training memory.
        $year = (int) \App\Models\AppSetting::getInt(\App\Models\AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);
        $nishabAnnual = app(\App\Services\Transactions\AnnualZakatDefaultsResolver::class)->resolve($year)->nishabAnnual();
        $nishabMonthly = (int) ($nishabAnnual / 12);

        return $this->mergeHintIntoContexts($contexts, [
            '_conversation_hint' => 'Mode percakapan: konsultasi zakat mal. '
                . 'Rangkum singkat data yang sudah diberikan user, tanyakan hanya data penting yang belum ada, '
                . 'dan jangan mengulang penjelasan umum kecuali diminta. Jika data belum cukup, beri opsi bernomor seperti '
                . '1) tidak ada hutang, 2) ada hutang jatuh tempo, 3) ada cicilan, 4) lainnya. '
                . 'Jika data sudah cukup, gunakan [HITUNG:{...}]. '
                . "Nisab yang berlaku saat ini: Rp" . number_format($nishabAnnual, 0, ',', '.') . " per tahun, "
                . "atau Rp" . number_format($nishabMonthly, 0, ',', '.') . " per bulan - kalau menyebut nisab, WAJIB pakai angka ini persis, jangan angka lain.",
        ]);
    }

    private function mergeHintIntoContexts(array $contexts, array $hint): array
    {
        if (empty($contexts)) {
            $contexts[] = $hint;
        } else {
            $contexts[0] = array_merge($contexts[0], $hint);
        }

        return $contexts;
    }
}

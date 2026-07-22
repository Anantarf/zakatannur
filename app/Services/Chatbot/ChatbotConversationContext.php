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

        $hasFinancialSignal = preg_match('/\d/', $normalized)
            || str_contains($normalized, 'gaji')
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

        return $this->mergeHintIntoContexts($contexts, [
            '_conversation_hint' => 'Mode percakapan: konsultasi zakat mal. '
                . 'Rangkum singkat data yang sudah diberikan user, tanyakan hanya data penting yang belum ada, '
                . 'dan jangan mengulang penjelasan umum kecuali diminta. Jika data belum cukup, beri opsi bernomor seperti '
                . '1) tidak ada hutang, 2) ada hutang jatuh tempo, 3) ada cicilan, 4) lainnya. '
                . 'Jika data sudah cukup, gunakan [HITUNG:{...}].',
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

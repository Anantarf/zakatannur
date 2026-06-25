<?php

namespace App\Services\Chatbot;

use App\Models\AiChatLog;
use App\Services\Chatbot\Knowledge\KnowledgeRetriever;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Services\Chatbot\ChatbotResponseCache;

class ChatbotOrchestrator
{
    public function __construct(
        private ChatbotServiceInterface $aiProvider,
        private ChatbotActionDetector $actionDetector,
        private KnowledgeRetriever $knowledgeRetriever,
        private ChatbotPublicDataResponder $publicDataResponder
    ) {
    }

    public function handle(string $message, array $rawContext = [], ?string $sessionId = null): ChatbotResponse
    {
        // Check cache for identical messages
        $cached = ChatbotResponseCache::get($message);
        if ($cached) {
            $this->saveChatLog($message, 'cached', 'cache', $cached->reply, $sessionId);
            return $cached;
        }

        $context = ChatbotConversationContext::fromArray($rawContext);

        try {
            $intent = $this->actionDetector->intent($message, $context);

            // Handle fitrah/fidyah calculation cases
            if ($intent === 'calculate_fitrah_case') {
                $response = $this->calculateFitrah($message);
                $this->saveChatLog($message, $intent, 'calculation', $response->reply, $sessionId, null, 'calculation');
                ChatbotResponseCache::put($message, $response);
                return $response;
            }

            if ($intent === 'calculate_fidyah_case') {
                $response = $this->calculateFidyah($message);
                $this->saveChatLog($message, $intent, 'calculation', $response->reply, $sessionId, null, 'calculation');
                ChatbotResponseCache::put($message, $response);
                return $response;
            }

            if ($intent === 'calculate_zakat_mal_case') {
                $response = $this->calculateZakatMal($message);
                $this->saveChatLog($message, $intent, 'calculation', $response->reply, $sessionId, null, 'calculation');
                ChatbotResponseCache::put($message, $response);
                return $response;
            }

            if ($intent === 'refer_zakat_mal_complex') {
                $response = $this->referTopanitia($message);
                $this->saveChatLog($message, $intent, 'referral', $response->reply, $sessionId, null, 'calculation');
                ChatbotResponseCache::put($message, $response);
                return $response;
            }

            // Route specific zakat mal intents to their knowledge base entries
            if (in_array($intent, ['ask_zakat_mal_definition', 'ask_zakat_mal_nishab', 'ask_zakat_mal_example'])) {
                $entryId = match($intent) {
                    'ask_zakat_mal_definition', 'ask_zakat_mal_nishab' => 'zakat-mal-definisi',
                    'ask_zakat_mal_example' => 'zakat-mal-contoh',
                    default => null,
                };

                if ($entryId) {
                    $knowledge = null;
                    foreach (config('zakky_knowledge', []) as $entry) {
                        if ($entry['id'] === $entryId) {
                            $knowledge = $entry;
                            break;
                        }
                    }

                    if ($knowledge) {
                        $response = ChatbotResponse::success(
                            (string) $knowledge['answer'],
                            'knowledge',
                            $knowledge['actions'] ?? [],
                            [['id' => $knowledge['id'], 'label' => $knowledge['source_label'] ?? 'Panduan Zakat Masjid An-Nur']]
                        )->withContext($context->forIntent($entryId, 'knowledge')->toArray());
                        $this->saveChatLog($message, $intent, 'knowledge', $response->reply, $sessionId, null, 'knowledge');
                        ChatbotResponseCache::put($message, $response);
                        return $response;
                    }
                }
            }

            $publicData = $intent ? $this->publicDataResponder->respond($intent) : null;
            if ($publicData) {
                $response = $publicData->withContext($context->forIntent($intent, 'public_data')->toArray());
                $this->saveChatLog($message, $intent, 'public_data', $response->reply, $sessionId, null, 'knowledge');
                ChatbotResponseCache::put($message, $response);
                return $response;
            }

            $action = $this->actionDetector->detect($message);
            if ($action) {
                $response = $action->withContext($context->forIntent('navigation', 'action')->toArray());
                $this->saveChatLog($message, 'navigation', 'action', $response->reply, $sessionId);
                ChatbotResponseCache::put($message, $response);
                return $response;
            }

            $knowledge = $this->knowledgeRetriever->best($message);
            if ($knowledge) {
                $response = ChatbotResponse::success(
                    (string) $knowledge['answer'],
                    'knowledge',
                    $knowledge['actions'] ?? [],
                    [[
                        'id' => $knowledge['id'] ?? null,
                        'label' => $knowledge['source_label'] ?? 'Panduan Zakat Masjid An-Nur',
                    ]]
                )->withContext($context->forIntent((string) ($knowledge['id'] ?? 'knowledge'), 'knowledge')->toArray());
                $this->saveChatLog($message, (string) ($knowledge['id'] ?? 'knowledge'), 'knowledge', $response->reply, $sessionId, null, 'knowledge');
                ChatbotResponseCache::put($message, $response);
                return $response;
            }

            $sentiment = ChatbotSentimentDetector::detect($message);
            $response = $this->answerFromAi($message);
            $confidenceSource = $this->aiProvider->wasLastReplyFallback() ? 'fallback' : 'ai';
            $this->saveChatLog($message, null, $response->source, $response->reply, $sessionId, $sentiment, $confidenceSource);
            ChatbotResponseCache::put($message, $response);
            return $response;
        } catch (Throwable $e) {
            Log::error('Chatbot orchestration failed.', [
                'message' => $e->getMessage(),
            ]);

            return ChatbotResponse::error('Gagal memproses pesan. Silakan coba beberapa saat lagi.', true, 500);
        }
    }

    private function saveChatLog(string $question, ?string $intent, string $sourceType, string $answer, ?string $sessionId, ?string $sentiment = null, ?string $confidenceSource = null): void
    {
        try {
            AiChatLog::updateOrCreate(
                [
                    'session_id' => $sessionId,
                    'question_md5' => md5($question),
                ],
                [
                    'question' => $question,
                    'intent' => $intent,
                    'source_type' => $sourceType,
                    'answer' => $answer,
                    'sentiment' => $sentiment,
                    'confidence_source' => $confidenceSource,
                ]
            );
        } catch (Throwable $e) {
            Log::warning('Failed to save AI chat log.', ['message' => $e->getMessage()]);
        }
    }

    private function answerFromAi(string $message): ChatbotResponse
    {
        $language = ChatbotLanguageDetector::detect($message);
        $sentiment = ChatbotSentimentDetector::detect($message);
        $contexts = $this->knowledgeRetriever->search($message, 2);

        // Adjust system prompt based on sentiment
        if ($sentiment === 'frustrated') {
            // Prepend empathy hint to first context chunk
            if (!empty($contexts)) {
                $contexts[0] = array_merge($contexts[0], [
                    '_sentiment_hint' => 'User appears frustrated. Be empathetic, concise, and offer clear next steps.',
                ]);
            }
        }

        $reply = $this->aiProvider->sendMessage($message, $contexts, $language);

        $wasFallback = $this->aiProvider->wasLastReplyFallback();
        $cleanReply = $wasFallback && str_starts_with($reply, ChatbotServiceInterface::FALLBACK_PREFIX)
            ? substr($reply, strlen(ChatbotServiceInterface::FALLBACK_PREFIX))
            : $reply;

        if ($wasFallback) {
            return ChatbotResponse::error($cleanReply, true, 503);
        }

        return ChatbotResponse::success($cleanReply, 'ai');
    }

    private function calculateFitrah(string $message): ChatbotResponse
    {
        // ponytail: regex extract number, no NLP — upgrade if multi-language parsing needed
        if (!preg_match('/(\d+)[\s]*(orang|jiwa|person)/', $message, $matches)) {
            return ChatbotResponse::success(
                'Saya butuh tahu berapa orang yang membayar fitrah. Coba tanya: "Fitrah 4 orang berapa?"',
                'knowledge',
                [['type' => 'suggested_reply', 'label' => 'Contoh', 'message' => 'Fitrah keluarga 4 orang berapa?']]
            );
        }

        $count = (int) $matches[1];
        $cashPerJiwa = config('zakat.annual_defaults.fitrah_cash_per_jiwa', 50000);
        $berasPerJiwa = config('zakat.annual_defaults.fitrah_beras_per_jiwa', 2.5);

        $totalCash = $count * $cashPerJiwa;
        $totalBeras = $count * $berasPerJiwa;

        $reply = sprintf(
            "PERHITUNGAN ZAKAT FITRAH UNTUK %d ORANG:\n\n"
            . "UANG:\n%d × Rp %s = Rp %s\n\n"
            . "BERAS:\n%d × %.1f kg = %.1f kg\n\n"
            . "Silakan konfirmasi ke Panitia Zakat An-Nur untuk validasi.",
            $count,
            $count, number_format($cashPerJiwa, 0, ',', '.'), number_format($totalCash, 0, ',', '.'),
            $count, $berasPerJiwa, $totalBeras
        );

        return ChatbotResponse::success($reply, 'calculation');
    }

    private function calculateFidyah(string $message): ChatbotResponse
    {
        // ponytail: regex extract number, no NLP
        if (!preg_match('/(\d+)[\s]*(hari|day)/', $message, $matches)) {
            return ChatbotResponse::success(
                'Saya butuh tahu berapa hari fidyah yang Anda bayar. Coba tanya: "Fidyah 7 hari berapa?"',
                'knowledge',
                [['type' => 'suggested_reply', 'label' => 'Contoh', 'message' => 'Fidyah 5 hari berapa?']]
            );
        }

        $days = (int) $matches[1];
        $cashPerHari = config('zakat.annual_defaults.fidyah_per_hari', 30000);
        $berasPerHari = config('zakat.annual_defaults.fidyah_beras_per_hari', 0.75);

        $totalCash = $days * $cashPerHari;
        $totalBeras = $days * $berasPerHari;

        $reply = sprintf(
            "PERHITUNGAN FIDYAH UNTUK %d HARI:\n\n"
            . "UANG:\n%d × Rp %s = Rp %s\n\n"
            . "BERAS:\n%d × %.2f kg = %.2f kg\n\n"
            . "Silakan konfirmasi ke Panitia Zakat An-Nur untuk validasi.",
            $days,
            $days, number_format($cashPerHari, 0, ',', '.'), number_format($totalCash, 0, ',', '.'),
            $days, $berasPerHari, $totalBeras
        );

        return ChatbotResponse::success($reply, 'calculation');
    }

    private function calculateZakatMal(string $message): ChatbotResponse
    {
        $guide = app(ChatbotZakatMalGuide::class);
        $data = $guide->detect($message);

        if (!$data || !array_filter($data)) {
            return ChatbotResponse::success(
                'Saya butuh informasi finansial Anda untuk hitung zakat mal. Coba tanya: "Saya PNS gaji 15 juta, tabungan 80 juta, emas 200 gram, zakat berapa?"',
                'knowledge',
                [['type' => 'suggested_reply', 'label' => 'Contoh', 'message' => 'Saya PNS gaji 15 juta, tabungan 80 juta, emas 200 gram, zakat berapa?']]
            );
        }

        $result = $guide->calculate($data);

        if (!$result['is_above_nishab']) {
            $reply = "PERHITUNGAN ZAKAT MAL ANDA:\n\n" .
                sprintf("Total Aset Neto: Rp %s\nNishab minimum: Rp %s\n\n",
                    number_format($result['nett_assets'], 0, ',', '.'),
                    number_format($result['nishab'], 0, ',', '.')
                ) .
                "Aset Anda BELUM mencapai nishab, jadi belum wajib zakat mal.\n\n" .
                "Perhitungan bersifat estimasi. Silakan konfirmasi ke Panitia Zakat An-Nur untuk kepastian.";
        } else {
            $reply = "PERHITUNGAN ZAKAT MAL ESTIMASI ANDA:\n\n" .
                sprintf("A. ASET YANG DIHITUNG:\n" .
                    "   • Penghasilan setahun: Rp %s\n" .
                    "   • Tabungan/cash: Rp %s\n" .
                    "   • Emas %dg (@ Rp 900rb/g): Rp %s\n" .
                    "   Total aset bruto: Rp %s\n\n" .
                    "B. DIKURANGI:\n" .
                    "   • Pengeluaran rutin (1 tahun): Rp %s\n" .
                    "   • Hutang: Rp %s\n\n" .
                    "C. CEK NISHAB:\n" .
                    "   Aset Neto: Rp %s (Nishab: Rp %s)\n" .
                    "   MELEBIHI NISHAB → WAJIB ZAKAT\n\n" .
                    "D. ZAKAT 2.5%%:\n" .
                    "   Rp %s × 2.5%% = Rp %s per tahun\n" .
                    "   (~Rp %s per bulan jika dicicil)\n\n",
                    number_format($result['annual_income'], 0, ',', '.'),
                    number_format($result['savings'] ?? 0, 0, ',', '.'),
                    $data['gold_gram'] ?? 0,
                    number_format($result['gold_value'], 0, ',', '.'),
                    number_format($result['total_assets'], 0, ',', '.'),
                    number_format($result['annual_expenses'], 0, ',', '.'),
                    number_format($result['debt'], 0, ',', '.'),
                    number_format($result['nett_assets'], 0, ',', '.'),
                    number_format($result['nishab'], 0, ',', '.'),
                    number_format($result['nett_assets'], 0, ',', '.'),
                    number_format($result['zakat_amount'], 0, ',', '.'),
                    number_format((int)($result['zakat_amount'] / 12), 0, ',', '.')
                ) .
                "PENTING:\n" .
                "• Perhitungan menggunakan standar umum ulama (BAZNAS, Syafi'i)\n" .
                "• Tarif An-Nur mungkin berbeda dari standar umum\n" .
                "• Harga emas fluktuatif, gunakan rate hari ini untuk akurasi\n" .
                "• ZAKKY BISA SALAH dalam kasus pribadi\n" .
                "• KONFIRMASI KE PANITIA ZAKAT AN-NUR SEBELUM BAYAR";
        }

        return ChatbotResponse::success($reply, 'calculation');
    }

    private function referTopanitia(string $message): ChatbotResponse
    {
        // ponytail: simple referral template, no case-specific messaging (same template for all complex cases)
        $reply = "Pertanyaan Anda tentang aset kompleks atau situasi khusus memerlukan konsultasi langsung dengan Panitia Zakat An-Nur.\n\n" .
            "Alasannya:\n" .
            "• Perhitungan membutuhkan verifikasi aset dan dokumen langsung\n" .
            "• Ada perbedaan fatwa antar mazhab untuk kasus Anda\n" .
            "• Panitia An-Nur mungkin punya ketentuan khusus yang tidak tercakup dalam kalkulator Zakky\n\n" .
            "HUBUNGI PANITIA ZAKAT MASJID AN-NUR:\n" .
            "Mereka siap memberikan konsultasi gratis untuk kasus pribadi Anda.\n" .
            "Panitia akan membantu Anda:\n" .
            "  1. Memahami aset kompleks Anda\n" .
            "  2. Menghitung zakat sesuai syariat dan aturan An-Nur\n" .
            "  3. Memastikan pembayaran akurat dan tepat waktu\n\n" .
            "Silakan datang ke Masjid An-Nur atau hubungi panitia langsung untuk diskusi lebih lanjut.";

        return ChatbotResponse::success($reply, 'referral', [
            ['type' => 'suggested_reply', 'label' => 'Info kontak panitia', 'message' => 'Bagaimana cara menghubungi panitia?'],
            ['type' => 'suggested_reply', 'label' => 'Kembali ke menu', 'message' => 'Menu utama'],
        ]);
    }
}

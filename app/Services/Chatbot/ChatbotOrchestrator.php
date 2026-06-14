<?php

namespace App\Services\Chatbot;

use App\Services\Chatbot\Knowledge\KnowledgeRetriever;
use App\Services\PublicSummaryService;
use App\Support\Format;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatbotOrchestrator
{
    public function __construct(
        private ChatbotServiceInterface $aiProvider,
        private ChatbotActionDetector $actionDetector,
        private KnowledgeRetriever $knowledgeRetriever,
        private PublicSummaryService $publicSummaryService
    ) {
    }

    public function handle(string $message): ChatbotResponse
    {
        try {
            $action = $this->actionDetector->detect($message);
            if ($action) {
                return $action;
            }

            $publicData = $this->answerFromPublicData($message);
            if ($publicData) {
                return $publicData;
            }

            $knowledge = $this->knowledgeRetriever->best($message);
            if ($knowledge) {
                return ChatbotResponse::success(
                    (string) $knowledge['answer'],
                    'knowledge',
                    $knowledge['actions'] ?? [],
                    [[
                        'id' => $knowledge['id'] ?? null,
                        'label' => $knowledge['source_label'] ?? 'Panduan Zakat Masjid An-Nur',
                    ]]
                );
            }

            return $this->answerFromAi($message);
        } catch (Throwable $e) {
            Log::error('Chatbot orchestration failed.', [
                'message' => $e->getMessage(),
            ]);

            return ChatbotResponse::error('Gagal memproses pesan. Silakan coba beberapa saat lagi.', true, 500);
        }
    }

    private function answerFromPublicData(string $message): ?ChatbotResponse
    {
        $normalized = mb_strtolower($message);
        $asksTotal = str_contains($normalized, 'total') || str_contains($normalized, 'berapa');
        $asksCategory = str_contains($normalized, 'kategori') || str_contains($normalized, 'jenis');

        if (!$asksTotal && !$asksCategory) {
            return null;
        }

        if (!str_contains($normalized, 'zakat') && !str_contains($normalized, 'penerimaan') && !str_contains($normalized, 'beras') && !str_contains($normalized, 'uang') && !str_contains($normalized, 'jiwa')) {
            return null;
        }

        $year = $this->publicSummaryService->resolveYear(null);
        $summary = $this->publicSummaryService->publicSummaryResponse($year)['data'] ?? [];
        $totals = $summary['totals'] ?? [];
        $items = $summary['items'] ?? [];

        if ($asksCategory) {
            if (count($items) === 0) {
                return ChatbotResponse::success('Belum ada kategori penerimaan yang tercatat untuk periode ini.', 'public_data');
            }

            $categories = collect($items)
                ->pluck('category')
                ->map(fn ($category) => ucwords(str_replace('_', ' ', (string) $category)))
                ->implode(', ');

            return ChatbotResponse::success("Kategori yang tercatat saat ini: {$categories}.", 'public_data');
        }

        $totalJiwa = (int) ($totals['total_jiwa'] ?? 0);
        $totalUang = (int) ($totals['total_uang'] ?? 0);
        $totalBeras = (float) ($totals['total_beras_kg'] ?? 0);

        if ($totalJiwa === 0 && $totalUang === 0 && $totalBeras <= 0.0) {
            return ChatbotResponse::success('Belum ada data penerimaan yang tercatat untuk periode ini.', 'public_data');
        }

        return ChatbotResponse::success(
            'Total penerimaan saat ini: '
            . number_format($totalJiwa, 0, ',', '.') . ' jiwa, '
            . Format::rupiah($totalUang) . ', dan '
            . Format::kg($totalBeras) . '. Data ini mengikuti ringkasan publik periode berjalan.',
            'public_data',
            [['type' => 'open_tab', 'target' => 'laporan']]
        );
    }

    private function answerFromAi(string $message): ChatbotResponse
    {
        $contexts = $this->knowledgeRetriever->search($message, 2);
        $reply = $this->aiProvider->sendMessage($message, $contexts);

        $wasFallback = $this->aiProvider->wasLastReplyFallback();
        $cleanReply = $wasFallback && str_starts_with($reply, ChatbotServiceInterface::FALLBACK_PREFIX)
            ? substr($reply, strlen(ChatbotServiceInterface::FALLBACK_PREFIX))
            : $reply;

        if ($wasFallback) {
            return ChatbotResponse::error($cleanReply, true, 503);
        }

        return ChatbotResponse::success($cleanReply, 'ai');
    }
}

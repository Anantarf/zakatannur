<?php

namespace App\Services\Chatbot;

use App\Services\PublicSummaryService;
use App\Support\Format;
use Carbon\Carbon;

class ChatbotPublicDataResponder
{
    public function __construct(private PublicSummaryService $publicSummaryService)
    {
    }

    public function respond(string $intent): ?ChatbotResponse
    {
        $year = $this->publicSummaryService->resolveYear(null);
        $summary = $this->publicSummaryService->publicSummaryResponse($year)['data'] ?? [];
        $totals = $summary['totals'] ?? [];
        $items = $summary['items'] ?? [];

        return match ($intent) {
            'ask_total_money' => $this->totalMoney($totals, $year),
            'ask_total_rice' => $this->totalRice($totals, $year),
            'ask_total_people' => $this->totalPeople($totals, $year),
            'ask_total_summary' => $this->totalSummary($totals, $year),
            'ask_categories' => $this->categories($items),
            'ask_top_category' => $this->topCategory($items),
            'ask_latest_update' => $this->latestUpdate($summary),
            default => null,
        };
    }

    private function totalMoney(array $totals, int $year): ChatbotResponse
    {
        $total = (int) ($totals['total_uang'] ?? 0);
        if ($total === 0) {
            return ChatbotResponse::success("Belum ada penerimaan uang yang tercatat untuk tahun {$year}.", 'public_data');
        }

        return ChatbotResponse::success(
            "Total penerimaan uang tahun {$year} saat ini adalah " . Format::rupiah($total) . '.',
            'public_data',
            $this->openSummaryAction()
        );
    }

    private function totalRice(array $totals, int $year): ChatbotResponse
    {
        $total = (float) ($totals['total_beras_kg'] ?? 0);
        if ($total <= 0.0) {
            return ChatbotResponse::success("Belum ada penerimaan beras yang tercatat untuk tahun {$year}.", 'public_data');
        }

        return ChatbotResponse::success(
            "Total penerimaan beras tahun {$year} saat ini adalah " . Format::kg($total) . '.',
            'public_data',
            $this->openSummaryAction()
        );
    }

    private function totalPeople(array $totals, int $year): ChatbotResponse
    {
        $total = (int) ($totals['total_jiwa'] ?? 0);
        if ($total === 0) {
            return ChatbotResponse::success("Belum ada jiwa zakat fitrah yang tercatat untuk tahun {$year}.", 'public_data');
        }

        return ChatbotResponse::success(
            "Total jiwa zakat fitrah tahun {$year} saat ini adalah " . number_format($total, 0, ',', '.') . ' jiwa.',
            'public_data',
            $this->openSummaryAction()
        );
    }

    private function totalSummary(array $totals, int $year): ChatbotResponse
    {
        $totalJiwa = (int) ($totals['total_jiwa'] ?? 0);
        $totalUang = (int) ($totals['total_uang'] ?? 0);
        $totalBeras = (float) ($totals['total_beras_kg'] ?? 0);

        if ($totalJiwa === 0 && $totalUang === 0 && $totalBeras <= 0.0) {
            return ChatbotResponse::success("Belum ada data penerimaan yang tercatat untuk tahun {$year}.", 'public_data');
        }

        return ChatbotResponse::success(
            "Ringkasan penerimaan tahun {$year}: " . Format::rupiah($totalUang) . ', ' . Format::kg($totalBeras) . ', dan ' . number_format($totalJiwa, 0, ',', '.') . ' jiwa.',
            'public_data',
            $this->openSummaryAction()
        );
    }

    private function categories(array $items): ChatbotResponse
    {
        if (count($items) === 0) {
            return ChatbotResponse::success('Belum ada kategori penerimaan yang tercatat untuk periode ini.', 'public_data');
        }

        $categories = collect($items)
            ->pluck('category')
            ->map(fn ($category) => $this->categoryLabel((string) $category))
            ->implode(', ');

        return ChatbotResponse::success("Kategori yang tercatat saat ini: {$categories}.", 'public_data', $this->openSummaryAction());
    }

    private function topCategory(array $items): ChatbotResponse
    {
        if (count($items) === 0) {
            return ChatbotResponse::success('Belum ada kategori penerimaan yang bisa dibandingkan.', 'public_data');
        }

        $top = collect($items)->sortByDesc(fn ($item) => (int) ($item['total_uang'] ?? 0))->first();
        if (!$top || (int) ($top['total_uang'] ?? 0) === 0) {
            return ChatbotResponse::success('Belum ada kategori dengan penerimaan uang yang tercatat.', 'public_data');
        }

        return ChatbotResponse::success(
            'Kategori dengan penerimaan uang terbesar saat ini adalah ' . $this->categoryLabel((string) ($top['category'] ?? '-')) . ' sebesar ' . Format::rupiah((int) ($top['total_uang'] ?? 0)) . '.',
            'public_data',
            $this->openSummaryAction()
        );
    }

    private function latestUpdate(array $summary): ChatbotResponse
    {
        $computedAt = $summary['computed_at_wib'] ?? null;
        if (!$computedAt) {
            return ChatbotResponse::success('Waktu pembaruan data publik belum tersedia.', 'public_data');
        }

        $time = Carbon::createFromFormat('d/m/Y H:i:s', $computedAt, config('zakat.timezone'));

        return ChatbotResponse::success(
            'Data publik terakhir diperbarui ' . $time->locale('id')->diffForHumans() . ' (' . $computedAt . ' WIB).',
            'public_data',
            $this->openSummaryAction()
        );
    }

    private function openSummaryAction(): array
    {
        return [
            ['type' => 'open_tab', 'target' => 'laporan', 'label' => 'Buka Ringkasan'],
            ['type' => 'suggested_reply', 'label' => 'Kategori terbesar', 'message' => 'Kategori terbesar apa?'],
            ['type' => 'suggested_reply', 'label' => 'Update terakhir', 'message' => 'Kapan data terakhir diperbarui?'],
        ];
    }

    private function categoryLabel(string $category): string
    {
        return ucwords(str_replace('_', ' ', $category));
    }
}

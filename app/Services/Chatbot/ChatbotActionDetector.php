<?php

namespace App\Services\Chatbot;

class ChatbotActionDetector
{
    public function intent(string $message, ?ChatbotConversationContext $context = null): ?string
    {
        $message = $this->normalize($message);

        if ($this->containsAny($message, ['update terakhir', 'terakhir update', 'diperbarui', 'kapan update', 'data terbaru'])) {
            return 'ask_latest_update';
        }

        if ($this->containsAny($message, ['kategori terbesar', 'paling besar', 'terbanyak', 'tertinggi'])) {
            return 'ask_top_category';
        }

        if ($this->containsAny($message, ['kategori', 'jenis zakat', 'jenis penerimaan'])) {
            return 'ask_categories';
        }

        if ($this->containsAny($message, ['beras', 'kg'])) {
            return 'ask_total_rice';
        }

        if ($this->containsAny($message, ['jiwa', 'orang', 'muzakki fitrah'])) {
            return 'ask_total_people';
        }

        if ($this->containsAny($message, ['uang', 'rupiah', 'rp', 'terkumpul', 'penerimaan uang', 'nominal'])) {
            return 'ask_total_money';
        }

        if ($this->containsAny($message, ['berapa', 'total', 'jumlah', 'ringkasan penerimaan', 'rekap penerimaan'])) {
            return 'ask_total_summary';
        }

        if ($this->containsAny($message, ['grafik', 'harian', 'chart', 'tren', 'pola penerimaan'])) {
            return 'open_chart';
        }

        if ($this->containsAny($message, ['ringkasan', 'laporan', 'rekap'])) {
            return 'open_summary';
        }

        if ($this->containsAny($message, ['cara bayar', 'bayar zakat', 'pembayaran', 'rekening', 'transfer'])) {
            return 'ask_payment_info';
        }

        if ($context?->isPublicDataTopic()) {
            return $this->publicDataFollowUpIntent($message);
        }

        return null;
    }

    public function detect(string $message): ?ChatbotResponse
    {
        $intent = $this->intent($message);
        if ($intent === 'open_summary') {
            return ChatbotResponse::success(
                'Saya buka tab Ringkasan Penerimaan. Di sana jamaah bisa melihat total jiwa, uang, beras, dan rincian kategori.',
                'action',
                [['type' => 'open_tab', 'target' => 'laporan', 'label' => 'Buka Ringkasan']]
            );
        }

        if ($intent === 'open_chart') {
            return ChatbotResponse::success(
                'Saya buka tab Grafik Harian. Di sana jamaah bisa melihat pola penerimaan per hari.',
                'action',
                [['type' => 'open_tab', 'target' => 'grafik', 'label' => 'Lihat Grafik']]
            );
        }

        return null;
    }

    private function normalize(string $message): string
    {
        $message = preg_replace('/[^\pL\pN\s]/u', ' ', mb_strtolower($message)) ?? '';

        return trim(preg_replace('/\s+/', ' ', $message) ?? '');
    }

    private function containsAny(string $message, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function publicDataFollowUpIntent(string $message): ?string
    {
        if ($this->containsAny($message, ['terakhir', 'terbaru', 'kapan'])) {
            return 'ask_latest_update';
        }

        if ($this->containsAny($message, ['terbesar', 'terbanyak', 'tertinggi'])) {
            return 'ask_top_category';
        }

        if ($this->containsAny($message, ['semua', 'semuanya', 'totalnya', 'jumlahnya', 'ringkas'])) {
            return 'ask_total_summary';
        }

        return null;
    }
}

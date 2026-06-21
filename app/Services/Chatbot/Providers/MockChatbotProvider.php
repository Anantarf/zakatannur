<?php

namespace App\Services\Chatbot\Providers;

use App\Services\Chatbot\ChatbotServiceInterface;

class MockChatbotProvider implements ChatbotServiceInterface
{
    public function sendMessage(string $message, array $context = []): string
    {
        $message = strtolower($message);
        if (str_contains($message, 'zakat') || str_contains($message, 'bayar')) {
            return 'Zakky bisa membantu membaca data publik dan panduan umum zakat. Untuk tata cara pembayaran yang berlaku hari ini, tetap ikuti arahan panitia Masjid An-Nur.';
        }

        if (str_contains($message, 'halo') || str_contains($message, 'hi') || str_contains($message, 'assalamualaikum') || str_contains($message, 'zakky')) {
            return 'Halo! Saya Zakky, asisten virtual Zakat An-Nur. Saya bisa bantu membaca ringkasan penerimaan, grafik, dan panduan umum zakat.';
        }

        return 'Saya belum punya jawaban pasti untuk pertanyaan itu. Coba tanyakan total uang, total beras, total jiwa, kategori penerimaan, update terakhir, atau cara bayar zakat.';
    }

    public function wasLastReplyFallback(): bool
    {
        return false;
    }
}

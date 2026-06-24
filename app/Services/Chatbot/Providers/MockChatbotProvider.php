<?php

namespace App\Services\Chatbot\Providers;

use App\Services\Chatbot\ChatbotServiceInterface;

class MockChatbotProvider implements ChatbotServiceInterface
{
    public function sendMessage(string $message, array $context = [], string $language = 'id'): string
    {
        $lower = strtolower($message);

        if ($language === 'en') {
            if (str_contains($lower, 'zakat') || str_contains($lower, 'pay')) {
                return 'Zakky can help you read public data and general zakat guidelines. For the current payment procedure, please follow the instructions from the Masjid An-Nur committee.';
            }

            if (str_contains($lower, 'hello') || str_contains($lower, 'hi') || str_contains($lower, 'zakky')) {
                return 'Hello! I\'m Zakky, the virtual assistant for Zakat An-Nur. I can help you read the collection summary, charts, and general zakat guidelines.';
            }

            return 'I don\'t have a precise answer to that question. Try asking about total money, total rice, total people, receiving categories, latest update, or how to pay zakat.';
        }

        if (str_contains($lower, 'zakat') || str_contains($lower, 'bayar')) {
            return 'Zakky bisa membantu membaca data publik dan panduan umum zakat. Untuk tata cara pembayaran yang berlaku hari ini, tetap ikuti arahan panitia Masjid An-Nur.';
        }

        if (str_contains($lower, 'halo') || str_contains($lower, 'hi') || str_contains($lower, 'assalamualaikum') || str_contains($lower, 'zakky')) {
            return 'Halo! Saya Zakky, asisten virtual Zakat An-Nur. Saya bisa bantu membaca ringkasan penerimaan, grafik, dan panduan umum zakat.';
        }

        return 'Saya belum punya jawaban pasti untuk pertanyaan itu. Coba tanyakan total uang, total beras, total jiwa, kategori penerimaan, update terakhir, atau cara bayar zakat.';
    }

    public function wasLastReplyFallback(): bool
    {
        return false;
    }
}

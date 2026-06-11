<?php

namespace App\Services\Chatbot\Providers;

use App\Services\Chatbot\ChatbotServiceInterface;

class MockChatbotProvider implements ChatbotServiceInterface
{
    public function sendMessage(string $message): string
    {
        $message = strtolower($message);
        if (str_contains($message, 'zakat') || str_contains($message, 'bayar')) {
            return "Untuk panduan zakat atau cara pembayaran, Anda bisa langsung mengakses menu Transaksi. Fitur AI penuh masih dalam tahap pengembangan.";
        }

        if (str_contains($message, 'halo') || str_contains($message, 'hi') || str_contains($message, 'assalamualaikum') || str_contains($message, 'zakky')) {
            return "Halo! Saya Zakky, asisten virtual Zakat An-Nur (versi simulasi). Ada yang bisa saya bantu terkait informasi zakat?";
        }

        return "Terima kasih atas pesannya: \"{$message}\". Saat ini AI Provider masih dalam tahap pertimbangan sehingga saya hanya bisa merespons dengan pesan otomatis. Insya Allah fitur penuh akan segera hadir!";
    }

    public function wasLastReplyFallback(): bool
    {
        return false;
    }
}

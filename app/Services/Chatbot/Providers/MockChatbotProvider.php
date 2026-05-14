<?php

namespace App\Services\Chatbot\Providers;

use App\Services\Chatbot\ChatbotServiceInterface;

class MockChatbotProvider implements ChatbotServiceInterface
{
    public function sendMessage(string $message): string
    {
        // Simulasi waktu proses API (misalnya 1 detik)
        sleep(1);

        // Simulasi balasan cerdas sederhana
        $message = strtolower($message);
        if (str_contains($message, 'zakat') || str_contains($message, 'bayar')) {
            return "Untuk panduan zakat atau cara pembayaran, Anda bisa langsung mengakses menu Transaksi. Fitur AI secara penuh masih dalam tahap pengembangan.";
        }

        if (str_contains($message, 'halo') || str_contains($message, 'hi')) {
            return "Halo! Saya adalah asisten virtual Zakat An-Nur (versi simulasi). Ada yang bisa saya bantu terkait informasi zakat?";
        }

        return "Terima kasih atas pesannya: \"{$message}\". Saat ini AI Provider masih dalam tahap pertimbangan sehingga saya hanya bisa merespons dengan pesan otomatis. Insya Allah fitur penuh akan segera hadir!";
    }
}

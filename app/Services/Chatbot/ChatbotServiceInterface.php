<?php

namespace App\Services\Chatbot;

interface ChatbotServiceInterface
{
    /**
     * Mengirim pesan ke AI provider dan mengembalikan balasannya.
     *
     * @param string $message
     * @return string
     */
    public function sendMessage(string $message): string;
}

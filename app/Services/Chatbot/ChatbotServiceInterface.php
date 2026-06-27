<?php

namespace App\Services\Chatbot;

interface ChatbotServiceInterface
{
    /**
     * Marker yang dipakai provider untuk menandai balasan fallback
     * (mis. saat API tidak tersedia). Format: "__FALLBACK__:pesan".
     * Controller akan membaca marker ini dan mengembalikan HTTP 503
     * agar frontend bisa membedakan "AI balas normal" vs "layanan down".
     */
    public const FALLBACK_PREFIX = '__FALLBACK__:';

    /**
     * Mengirim pesan ke AI provider dan mengembalikan balasannya.
     * Jika provider tidak dapat menjangkau AI upstream, kembalikan
     * string dengan awalan FALLBACK_PREFIX agar controller bisa
     * membedakan balasan normal dari kondisi error upstream.
     *
     * @param string $message
     * @param array<int,array<string,mixed>> $context
     * @param string $language 'id' or 'en'
     * @return string
     */
    public function sendMessage(string $message, array $context = [], string $language = 'id', array $history = []): string;

    /**
     * Apakah balasan terakhir merupakan fallback (layanan AI tidak tersedia).
     * Frontend/Controller bisa pakai ini untuk membedakan status code.
     */
    public function wasLastReplyFallback(): bool;
}

<?php

namespace App\Console\Commands;

use App\Services\Chatbot\ChatbotOrchestrator;
use App\Services\Chatbot\Knowledge\ChatbotBehaviorDataset;
use Illuminate\Console\Command;

class EvaluateChatbotBehavior extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chatbot:eval-behavior';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Uji perilaku multi-turn chatbot (niat, ketepatan tanya-vs-hitung, retensi konteks) terhadap skenario baku (butuh API key asli - jalankan manual sebelum perubahan besar ke system prompt).';

    public function handle(ChatbotOrchestrator $orchestrator): int
    {
        $rows = [];
        $failures = 0;
        $cases = ChatbotBehaviorDataset::cases();

        foreach ($cases as $case) {
            // Fresh, unique session per case so AiChatLog history from a previous eval run (or
            // another case) never bleeds into this one - each scenario starts from a clean slate.
            $sessionId = 'eval-behavior-' . uniqid();
            $rawContext = [];
            $lastReply = '';

            foreach ($case['turns'] as $turn) {
                $response = $orchestrator->handle($turn, $rawContext, $sessionId);
                $lastReply = $response->reply;
                // Round-trip context like the frontend does, so mode (e.g. zakat_mal_consultation)
                // and topic hints survive across turns within the same scenario.
                $rawContext = $response->context;
            }

            $pass = ($case['expect'])($lastReply);
            $pass ? null : $failures++;

            $rows[] = [
                $pass ? 'OK' : 'GAGAL',
                $case['name'],
                $case['expect_description'],
                mb_strimwidth($lastReply, 0, 80, '...'),
            ];
        }

        $this->info('=== Skenario perilaku multi-turn ===');
        $this->table(['Status', 'Skenario', 'Ekspektasi', 'Balasan terakhir (dipotong)'], $rows);

        $this->newLine();
        $this->info('=== Ringkasan ===');
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Total skenario', count($cases)],
                ['Lolos', count($cases) - $failures],
                ['Gagal', $failures],
            ]
        );

        return $failures === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}

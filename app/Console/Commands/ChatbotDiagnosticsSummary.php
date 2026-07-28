<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

// Summarizes storage/logs/chatbot-*.log (written by App\Services\Chatbot\ChatbotDiagnostics) into
// a "which layer is acting up" table, so a failure/anomaly can be traced to a specific layer
// (fast-path routing, RAG retrieval, LLM call, sentinel calculation, guardrail, safety classifier,
// orchestrator) without manually grepping the log file.
class ChatbotDiagnosticsSummary extends Command
{
    protected $signature = 'chatbot:diagnostics {--days=1 : Berapa hari file log chatbot-*.log yang dibaca}';

    protected $description = 'Ringkas storage/logs/chatbot-*.log per layer/event, supaya cepat tahu layer mana yang paling sering blokir/gagal.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $counts = [];
        $totalLines = 0;
        $filesRead = [];

        for ($i = 0; $i < $days; $i++) {
            $path = storage_path('logs/chatbot-' . now()->subDays($i)->format('Y-m-d') . '.log');
            if (!file_exists($path)) {
                continue;
            }

            $filesRead[] = basename($path);
            $handle = fopen($path, 'r');
            if ($handle === false) {
                continue;
            }

            while (($line = fgets($handle)) !== false) {
                if (!preg_match('/^\[[\d\- :]+\]\s+\S+\.(?<level>\w+):\s+\[(?<layer>[^\]]+)\]\s+(?<event>\S+)/', $line, $m)) {
                    continue;
                }

                $totalLines++;
                $key = $m['layer'] . '|' . $m['event'] . '|' . $m['level'];
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }

            fclose($handle);
        }

        if (empty($filesRead)) {
            $this->warn("Tidak ada file storage/logs/chatbot-*.log untuk {$days} hari terakhir. Belum ada aktivitas chatbot tercatat, atau log-nya sudah kedaluwarsa (retensi 14 hari).");

            return self::SUCCESS;
        }

        arsort($counts);

        $rows = [];
        foreach ($counts as $key => $count) {
            [$layer, $event, $level] = explode('|', $key);
            $rows[] = [$layer, $event, $level, $count];
        }

        $this->info("Membaca: " . implode(', ', $filesRead) . " ({$totalLines} entri berlabel layer)");
        $this->table(['Layer', 'Event', 'Level', 'Jumlah'], $rows);

        $warningOrWorse = array_filter($rows, fn ($r) => in_array($r[2], ['WARNING', 'ERROR', 'CRITICAL'], true));
        if (!empty($warningOrWorse)) {
            $this->newLine();
            $this->warn('Layer dengan warning/error - ini titik paling relevan untuk dicek duluan kalau ada yang dirasa aneh:');
            foreach ($warningOrWorse as $r) {
                $this->line("  [{$r[0]}] {$r[1]} ({$r[2]}) x{$r[3]}");
            }
        }

        return self::SUCCESS;
    }
}

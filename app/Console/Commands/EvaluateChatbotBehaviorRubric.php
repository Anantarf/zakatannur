<?php

namespace App\Console\Commands;

use App\Services\Chatbot\ChatbotOrchestrator;
use App\Services\Chatbot\Knowledge\ChatbotBehaviorRubricDataset;
use Illuminate\Console\Command;

class EvaluateChatbotBehaviorRubric extends Command
{
    protected $signature = 'chatbot:eval-behavior-rubric {--markdown : Output markdown table for manual scoring}';

    protected $description = 'Buat bahan evaluasi rubric perilaku konsultan Zakky (multi-turn, skor manual 1-5; butuh API key asli).';

    public function handle(ChatbotOrchestrator $orchestrator): int
    {
        $cases = ChatbotBehaviorRubricDataset::cases();
        $aspects = ChatbotBehaviorRubricDataset::rubricAspects();
        $rows = [];

        foreach ($cases as $index => $case) {
            $sessionId = 'eval-rubric-' . uniqid();
            $rawContext = [];
            $lastReply = '';

            foreach ($case['turns'] as $turn) {
                $response = $orchestrator->handle($turn, $rawContext, $sessionId);
                $lastReply = $response->reply;
                $rawContext = $response->context;
            }

            $rows[] = [
                'no' => $index + 1,
                'name' => $case['name'],
                'focus' => $case['focus'],
                'turns' => implode(' / ', $case['turns']),
                'reply' => $lastReply,
                'notes' => $case['notes'],
            ];
        }

        if ($this->option('markdown')) {
            $this->renderMarkdown($rows, $aspects);
            return Command::SUCCESS;
        }

        $this->info('=== Rubric Evaluasi Perilaku Konsultan Zakky ===');
        $this->line('Skor manual: 1 = buruk, 2 = kurang, 3 = cukup, 4 = baik, 5 = sangat baik.');
        $this->newLine();
        $this->table(
            ['No', 'Skenario', 'Fokus', 'Balasan terakhir (dipotong)', 'Catatan evaluator'],
            array_map(fn ($row) => [
                $row['no'],
                $row['name'],
                $row['focus'],
                mb_strimwidth($row['reply'], 0, 120, '...'),
                '',
            ], $rows)
        );

        $this->newLine();
        $this->info('=== Aspek Rubric ===');
        $this->table(
            ['Aspek', 'Skor 1-5'],
            array_map(fn ($aspect) => [$aspect['label'], ''], $aspects)
        );

        $this->newLine();
        $this->line('Gunakan --markdown untuk tabel yang mudah ditempel ke dokumen evaluasi manual.');

        return Command::SUCCESS;
    }

    private function renderMarkdown(array $rows, array $aspects): void
    {
        $headers = array_merge(
            ['No', 'Skenario', 'Fokus', 'Balasan Zakky', 'Catatan'],
            array_map(fn ($aspect) => $aspect['label'], $aspects)
        );

        $this->line('| ' . implode(' | ', $headers) . ' |');
        $this->line('| ' . implode(' | ', array_fill(0, count($headers), '---')) . ' |');

        foreach ($rows as $row) {
            $cells = [
                (string) $row['no'],
                $row['name'],
                $row['focus'],
                $this->markdownCell($row['reply']),
                '',
            ];

            $cells = array_merge($cells, array_fill(0, count($aspects), ''));

            $this->line('| ' . implode(' | ', array_map([$this, 'markdownCell'], $cells)) . ' |');
        }
    }

    private function markdownCell(string $value): string
    {
        return str_replace(["\r", "\n", '|'], [' ', '<br>', '\\|'], trim($value));
    }
}

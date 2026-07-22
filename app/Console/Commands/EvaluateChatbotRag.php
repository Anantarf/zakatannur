<?php

namespace App\Console\Commands;

use App\Services\Chatbot\ChatbotOrchestrator;
use App\Services\Chatbot\Knowledge\ChatbotEvalDataset;
use App\Services\Chatbot\Knowledge\KnowledgeRetriever;
use Illuminate\Console\Command;

class EvaluateChatbotRag extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chatbot:eval-rag';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Uji kualitas semantic search + jawaban akhir chatbot terhadap set pertanyaan baku (butuh API key asli - jalankan manual sebelum perubahan besar ke KB/prompt).';

    public function handle(KnowledgeRetriever $retriever, ChatbotOrchestrator $orchestrator): int
    {
        $rows = [];
        $truePositives = 0;
        $falseNegatives = 0;
        $factFailures = 0;
        $cases = ChatbotEvalDataset::cases();

        foreach ($cases as $case) {
            $results = $retriever->search($case['question'], 3);
            $slugs = collect($results)->pluck('id')->all();
            $retrievalPass = in_array($case['expected_slug'], $slugs, true);
            $retrievalPass ? $truePositives++ : $falseNegatives++;

            $topScore = isset($results[0])
                ? ($results[0]['_cosine_similarity'] ?? $results[0]['_score'] ?? '-')
                : '-';

            // Real LLM call - only made when this case actually checks a fact, so cases
            // without one (open-ended answers, unsafe to substring-match) stay free.
            $factStatus = '-';
            if ($case['fact'] !== null) {
                $reply = $orchestrator->handle($case['question'])->reply;
                $factStatus = str_contains($reply, $case['fact']) ? 'OK' : 'GAGAL';
                if ($factStatus === 'GAGAL') {
                    $factFailures++;
                }
            }

            $pass = $retrievalPass && $factStatus !== 'GAGAL';

            $rows[] = [
                $pass ? 'OK' : 'GAGAL',
                $case['question'],
                $case['expected_slug'],
                implode(', ', $slugs) ?: '(kosong)',
                is_float($topScore) ? round($topScore, 3) : $topScore,
                $factStatus,
            ];
        }

        $this->info('=== Kasus positif (harus menemukan topik yang tepat) ===');
        $this->table(['Status', 'Pertanyaan', 'Slug diharapkan', 'Top-3 hasil', 'Skor teratas', 'Cek fakta'], $rows);

        $negativeRows = [];
        $trueNegatives = 0;
        $falsePositives = 0;

        foreach (ChatbotEvalDataset::negativeCases() as $case) {
            $results = $retriever->search($case['question'], 3);
            $slugs = collect($results)->pluck('id')->all();
            $isFalsePositive = !empty($slugs);
            $isFalsePositive ? $falsePositives++ : $trueNegatives++;

            $negativeRows[] = [
                $isFalsePositive ? 'FALSE POSITIVE' : 'OK',
                $case['question'],
                implode(', ', $slugs) ?: '(kosong, sesuai harapan)',
            ];
        }

        $this->newLine();
        $this->info('=== Kasus negatif / out-of-scope (harus kosong) ===');
        $this->table(['Status', 'Pertanyaan out-of-scope', 'Hasil (harusnya kosong)'], $negativeRows);

        $precision = ($truePositives + $falsePositives) > 0
            ? $truePositives / ($truePositives + $falsePositives)
            : 0.0;
        $recall = ($truePositives + $falseNegatives) > 0
            ? $truePositives / ($truePositives + $falseNegatives)
            : 0.0;
        $specificity = ($trueNegatives + $falsePositives) > 0
            ? $trueNegatives / ($trueNegatives + $falsePositives)
            : 0.0;
        $f1 = ($precision + $recall) > 0
            ? 2 * ($precision * $recall) / ($precision + $recall)
            : 0.0;

        $this->newLine();
        $this->info('=== Confusion matrix & metrik ===');
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['True Positive', $truePositives],
                ['False Negative', $falseNegatives],
                ['True Negative', $trueNegatives],
                ['False Positive', $falsePositives],
                ['Precision', round($precision, 3)],
                ['Recall', round($recall, 3)],
                ['Specificity', round($specificity, 3)],
                ['F1-Score', round($f1, 3)],
                ['Fact-check gagal', $factFailures],
            ]
        );

        // Exit status covers retrieval (false negatives/positives) AND fact-check failures -
        // a case that retrieves the right topic but gets the number wrong must still fail the
        // gate, not just show "GAGAL" in a table nobody's script is parsing.
        return ($falseNegatives === 0 && $falsePositives === 0 && $factFailures === 0)
            ? Command::SUCCESS
            : Command::FAILURE;
    }
}

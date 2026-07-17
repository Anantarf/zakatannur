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
        $passCount = 0;
        $cases = ChatbotEvalDataset::cases();

        foreach ($cases as $case) {
            $results = $retriever->search($case['question'], 3);
            $slugs = collect($results)->pluck('id')->all();
            $retrievalPass = in_array($case['expected_slug'], $slugs, true);

            $topScore = isset($results[0])
                ? ($results[0]['_cosine_similarity'] ?? $results[0]['_score'] ?? '-')
                : '-';

            // Real LLM call - only made when this case actually checks a fact, so cases
            // without one (open-ended answers, unsafe to substring-match) stay free.
            $factStatus = '-';
            if ($case['fact'] !== null) {
                $reply = $orchestrator->handle($case['question'])->reply;
                $factStatus = str_contains($reply, $case['fact']) ? 'OK' : 'GAGAL';
            }

            $pass = $retrievalPass && $factStatus !== 'GAGAL';
            $passCount += $pass ? 1 : 0;

            $rows[] = [
                $pass ? 'OK' : 'GAGAL',
                $case['question'],
                $case['expected_slug'],
                implode(', ', $slugs) ?: '(kosong)',
                is_float($topScore) ? round($topScore, 3) : $topScore,
                $factStatus,
            ];
        }

        $this->table(['Status', 'Pertanyaan', 'Slug diharapkan', 'Top-3 hasil', 'Skor teratas', 'Cek fakta'], $rows);
        $this->info("{$passCount}/" . count($cases) . ' pertanyaan lolos (retrieval' . ' + cek fakta bila ada).');

        return $passCount === count($cases) ? Command::SUCCESS : Command::FAILURE;
    }
}

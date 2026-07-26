<?php

namespace App\Console\Commands;

use App\Services\Chatbot\Safety\ChatbotSafetyClassifier;
use App\Services\Chatbot\Safety\ChatbotSafetyDataset;
use App\Services\Chatbot\Safety\ChatbotSafetyEmbeddingsCache;
use Illuminate\Console\Command;

class EvaluateChatbotSafety extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chatbot:eval-safety';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uji akurasi classifier keamanan berbasis embedding similarity terhadap ChatbotSafetyDataset via leave-one-out cross-validation (butuh cache embedding - jalankan chatbot:cache-safety-embeddings dulu).';

    public function handle(ChatbotSafetyClassifier $classifier, ChatbotSafetyEmbeddingsCache $embeddingsCache): int
    {
        $cases = ChatbotSafetyDataset::cases();
        $embeddings = $embeddingsCache->getCachedEmbeddings();

        if (count($embeddings) < count($cases)) {
            $this->error(sprintf(
                'Embedding cache belum lengkap (%d dari %d kasus). Jalankan `php artisan chatbot:cache-safety-embeddings` dulu.',
                count($embeddings),
                count($cases)
            ));

            return self::FAILURE;
        }

        $rows = [];
        $confusion = []; // [actual][predicted] => count
        $confidentCorrect = 0;
        $confidentTotal = 0;
        $ambiguousCount = 0;
        $noMatchCount = 0;
        $correct = 0;
        $perCase = []; // score/correct/is_in_domain per case, reused below for the threshold sweep

        foreach ($cases as $index => $case) {
            // Leave-one-out: classify this case's own cached vector against every OTHER case's
            // vector, never against itself - otherwise every case would trivially match itself
            // at similarity 1.0 and the eval would measure nothing.
            $reference = $embeddings;
            unset($reference[$index]);

            $result = $classifier->classifyVector($embeddings[$index], $reference);
            $predicted = $result['category'] ?? '(tidak ada)';
            $score = $result['score'] ?? 0.0;
            $confidence = $result['confidence'] ?? 'no_match';

            $isCorrect = $predicted === $case['category'];
            $isCorrect ? $correct++ : null;

            $confusion[$case['category']] ??= [];
            $confusion[$case['category']][$predicted] = ($confusion[$case['category']][$predicted] ?? 0) + 1;

            match ($confidence) {
                'confident' => [$confidentTotal++, $isCorrect ? $confidentCorrect++ : null],
                'ambiguous' => $ambiguousCount++,
                default => $noMatchCount++,
            };

            $perCase[] = [
                'score' => $score,
                'correct' => $isCorrect,
                'is_in_domain' => $case['category'] === ChatbotSafetyDataset::CATEGORY_IN_DOMAIN,
            ];

            $rows[] = [
                $isCorrect ? 'OK' : 'GAGAL',
                $case['category'],
                $predicted,
                round($score, 3),
                $confidence,
                mb_strimwidth($case['text'], 0, 50, '...'),
            ];
        }

        $this->info('=== Hasil klasifikasi per kasus (leave-one-out) ===');
        $this->table(['Status', 'Kategori asli', 'Prediksi', 'Skor', 'Keyakinan', 'Teks (dipotong)'], $rows);

        $this->newLine();
        $this->info('=== Confusion matrix (baris = kategori asli, kolom = prediksi) ===');
        $categories = array_unique(array_column($cases, 'category'));
        sort($categories);
        $matrixRows = [];
        foreach ($categories as $actual) {
            $row = [$actual];
            foreach ($categories as $predicted) {
                $row[] = $confusion[$actual][$predicted] ?? 0;
            }
            $matrixRows[] = $row;
        }
        $this->table(array_merge(['Aktual \\ Prediksi'], $categories), $matrixRows);

        $this->newLine();
        $this->info('=== Kategori paling sering salah klasifikasi ===');
        $mistakeRows = [];
        foreach ($categories as $actual) {
            $total = array_sum($confusion[$actual] ?? []);
            $wrong = $total - ($confusion[$actual][$actual] ?? 0);
            $mistakeRows[] = [$actual, $total, $wrong, $total > 0 ? round($wrong / $total, 3) : 0];
        }
        usort($mistakeRows, fn ($a, $b) => $b[3] <=> $a[3]);
        $this->table(['Kategori', 'Total kasus', 'Salah klasifikasi', 'Error rate'], $mistakeRows);

        $this->newLine();
        $this->info('=== Threshold sweep (cari titik potong "confident" terbaik dari data yang sama) ===');
        // Reuses the leave-one-out scores already computed above - no extra embedding calls.
        // "FP in_domain" is the safety-critical number: how often a legitimate zakat question
        // gets confidently misclassified into a risky category and wrongly blocked at that
        // threshold. Accuracy alone can hide a threshold that's "mostly right" but blocks real
        // users - report both, and prefer the lowest FP rate among the higher-accuracy options.
        $inDomainTotal = count(array_filter($perCase, fn ($c) => $c['is_in_domain']));
        $sweepRows = [];
        for ($threshold = 0.30; $threshold <= 0.75; $threshold += 0.02) {
            $atOrAbove = array_filter($perCase, fn ($c) => $c['score'] >= $threshold);
            $count = count($atOrAbove);
            $correctAtThreshold = count(array_filter($atOrAbove, fn ($c) => $c['correct']));
            $inDomainFalsePositives = count(array_filter(
                $atOrAbove,
                fn ($c) => $c['is_in_domain'] && !$c['correct']
            ));

            $sweepRows[] = [
                round($threshold, 2),
                $count,
                round($count / count($cases), 3),
                $count > 0 ? round($correctAtThreshold / $count, 3) : 0,
                $inDomainFalsePositives,
                $inDomainTotal > 0 ? round($inDomainFalsePositives / $inDomainTotal, 3) : 0,
            ];
        }
        $this->table(['Threshold', 'Kasus >= threshold', 'Cakupan', 'Akurasi', 'FP in_domain (n)', 'FP in_domain rate'], $sweepRows);

        $total = count($cases);
        $accuracy = $total > 0 ? $correct / $total : 0.0;
        $confidentAccuracy = $confidentTotal > 0 ? $confidentCorrect / $confidentTotal : 0.0;
        $confidentCoverage = $total > 0 ? $confidentTotal / $total : 0.0;

        $this->newLine();
        $this->info('=== Ringkasan ===');
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Total kasus', $total],
                ['Top-1 akurasi (semua tingkat keyakinan)', round($accuracy, 3)],
                ['Kasus "confident" (skor >= ' . ChatbotSafetyClassifier::CONFIDENT_THRESHOLD . ')', $confidentTotal],
                ['Akurasi khusus kasus "confident"', round($confidentAccuracy, 3)],
                ['Cakupan "confident" dari total', round($confidentCoverage, 3)],
                ['Kasus "ambiguous" (' . ChatbotSafetyClassifier::AMBIGUOUS_THRESHOLD . '-' . ChatbotSafetyClassifier::CONFIDENT_THRESHOLD . ')', $ambiguousCount],
                ['Kasus "no_match" (< ' . ChatbotSafetyClassifier::AMBIGUOUS_THRESHOLD . ')', $noMatchCount],
            ]
        );

        // A safety classifier that's wrong when confident is worse than one that's honestly
        // unsure - gate on confident-tier accuracy, not raw top-1, since only "confident" results
        // actually block a reply in ChatbotSafetyClassifier::checkReply().
        return $confidentAccuracy >= 0.8 ? self::SUCCESS : self::FAILURE;
    }
}

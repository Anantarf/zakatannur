<?php

namespace App\Console\Commands;

use App\Models\ZakatTransaction;
use App\Services\Transactions\TransactionReviewAssistantService;
use Illuminate\Console\Command;

class BackfillTransactionRiskReviews extends Command
{
    protected $signature = 'transactions:backfill-risk-reviews {--chunk=100 : Number of transactions processed per batch}';

    protected $description = 'Backfill review risiko untuk transaksi aktif lama yang belum memiliki record review.';

    public function handle(TransactionReviewAssistantService $reviewAssistantService): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));

        $baseQuery = ZakatTransaction::query()
            ->valid()
            ->whereDoesntHave('riskReview');

        $total = (clone $baseQuery)->count();

        if ($total === 0) {
            $this->info('Tidak ada transaksi aktif yang perlu dibackfill.');

            return self::SUCCESS;
        }

        $this->info("Memulai backfill review risiko untuk {$total} transaksi aktif...");

        $processed = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $baseQuery
            ->orderBy('id')
            ->chunkById($chunkSize, function ($transactions) use ($reviewAssistantService, &$processed, $bar) {
                $reviewAssistantService->syncForTransactions($transactions);
                $count = $transactions->count();
                $processed += $count;
                $bar->advance($count);
            });

        $bar->finish();
        $this->newLine(2);
        $this->info("Backfill selesai. {$processed} transaksi diproses.");

        return self::SUCCESS;
    }
}

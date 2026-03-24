<?php

namespace App\Console\Commands;

use App\Models\ZakatTransaction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PurgeOldTrashedTransactions extends Command
{
    protected $signature = 'transactions:purge-trash {--days=30 : Days after soft-delete before permanent removal}';
    protected $description = 'Permanently delete soft-deleted transactions older than the specified number of days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = Carbon::now()->subDays($days);

        // Purge Transactions
        $txCount = ZakatTransaction::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->forceDelete();

        // Purge Muzakki - ONLY if they have no transactions (including trashed ones)
        // This prevents orphaning valid or recently deleted transactions.
        $mzCount = \App\Models\Muzakki::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->whereDoesntHave('transactions', function($q) {
                $q->withTrashed();
            })
            ->forceDelete();

        if ($txCount === 0 && $mzCount === 0) {
            $this->info('No trashed data older than ' . $days . ' days found.');
            return self::SUCCESS;
        }

        if ($txCount > 0) {
            $this->info("Permanently deleted {$txCount} trashed transaction(s).");
        }
        
        if ($mzCount > 0) {
            $this->info("Permanently deleted {$mzCount} trashed muzakki record(s).");
        }

        return self::SUCCESS;
    }
}

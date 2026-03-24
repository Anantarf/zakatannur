<?php

namespace App\Console\Commands;

use App\Models\Muzakki;
use App\Models\ZakatTransaction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PurgeOldTrashedMuzakki extends Command
{
    protected $signature = 'muzakki:purge-trash {--days=30 : Days after soft-delete before permanent removal}';
    protected $description = 'Permanently delete soft-deleted Muzakki records older than the specified number of days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = Carbon::now()->subDays($days);

        // Get count of total trashed candidates before deletion
        $totalCandidates = Muzakki::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->count();

        if ($totalCandidates === 0) {
            $this->info('No trashed muzakki older than ' . $days . ' days found.');
            return self::SUCCESS;
        }

        // Perform bulk force delete on candidates that DO NOT have any associated transactions (including trashed ones)
        $deletedCount = Muzakki::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->whereDoesntHave('transactions', function($q) {
                $q->withTrashed();
            })
            ->forceDelete();

        $this->info("Permanently deleted {$deletedCount} trashed muzakki record(s) older than {$days} days.");
        
        $skipped = $totalCandidates - $deletedCount;
        if ($skipped > 0) {
            $this->warn("Skipped {$skipped} record(s) because they still have associated transactions.");
        }

        return self::SUCCESS;
    }
}

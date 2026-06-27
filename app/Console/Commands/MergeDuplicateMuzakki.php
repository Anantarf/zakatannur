<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Muzakki;
use App\Models\ZakatTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MergeDuplicateMuzakki extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'muzakki:merge-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge duplicate muzakki records based on exact name and address match';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to merge duplicate Muzakki records...');

        // Find duplicates grouped by name only
        $duplicates = Muzakki::select('name', DB::raw('count(*) as count'))
            ->groupBy('name')
            ->havingRaw('count > 1')
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicates found.');
            return;
        }

        $this->info('Found ' . $duplicates->count() . ' groups of duplicates.');

        $mergedGroups = 0;
        $deletedRecords = 0;
        $updatedTransactions = 0;

        DB::beginTransaction();

        try {
            foreach ($duplicates as $duplicate) {
                // Get all records for this group, ordered by id (oldest first)
                $records = Muzakki::where('name', $duplicate->name)
                    ->orderBy('id', 'asc')
                    ->get();

                if ($records->count() <= 1) {
                    continue; // Double check
                }

                // First record is the master
                $master = $records->first();
                $duplicateRecords = $records->slice(1);
                $duplicateIds = $duplicateRecords->pluck('id')->toArray();

                $this->line("Merging group: {$duplicate->name} - Master ID: {$master->id}, Duplicate IDs: " . implode(', ', $duplicateIds));

                // Check if master needs a phone number or address from duplicates
                $needsSave = false;
                if (empty($master->phone)) {
                    foreach ($duplicateRecords as $dup) {
                        if (!empty($dup->phone)) {
                            $master->phone = $dup->phone;
                            $this->line("  -> Updated Master ID {$master->id} with phone number {$dup->phone}");
                            $needsSave = true;
                            break;
                        }
                    }
                }
                
                if (empty($master->address)) {
                    foreach ($duplicateRecords as $dup) {
                        if (!empty($dup->address) && $dup->address !== '-') {
                            $master->address = $dup->address;
                            $this->line("  -> Updated Master ID {$master->id} with address {$dup->address}");
                            $needsSave = true;
                            break;
                        }
                    }
                }

                if ($needsSave) {
                    $master->save();
                }

                // Update ZakatTransactions
                $affected = ZakatTransaction::whereIn('muzakki_id', $duplicateIds)
                    ->update(['muzakki_id' => $master->id]);

                if ($affected > 0) {
                    $this->line("  -> Moved $affected transactions to Master ID {$master->id}");
                    $updatedTransactions += $affected;
                }

                // Delete duplicate records
                Muzakki::whereIn('id', $duplicateIds)->delete(); // this performs soft delete due to SoftDeletes trait on model
                $this->line("  -> Deleted " . count($duplicateIds) . " duplicate records.");

                $deletedRecords += count($duplicateIds);
                $mergedGroups++;
            }

            DB::commit();

            $this->info("Successfully merged $mergedGroups groups.");
            $this->info("Deleted (soft delete) $deletedRecords duplicate records.");
            $this->info("Updated $updatedTransactions transactions to point to master records.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('An error occurred during merge: ' . $e->getMessage());
            Log::error('MergeDuplicateMuzakki Error: ' . $e->getMessage());
        }
    }
}

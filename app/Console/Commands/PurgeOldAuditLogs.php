<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class PurgeOldAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit-logs:purge';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Hapus log audit yang sudah lebih dari 30 hari secara otomatis.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai pembersihan log audit lama...');

        $count = AuditLog::where('created_at', '<', now()->subDays(30))->delete();

        $this->info("Berhasil menghapus {$count} baris log yang sudah berumur lebih dari 30 hari.");

        return Command::SUCCESS;
    }
}

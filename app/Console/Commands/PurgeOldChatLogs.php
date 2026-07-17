<?php

namespace App\Console\Commands;

use App\Models\AiChatLog;
use Illuminate\Console\Command;

class PurgeOldChatLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chatbot-logs:purge';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Hapus log percakapan chatbot yang sudah lebih dari 30 hari secara otomatis.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai pembersihan log chatbot lama...');

        $purgeDays = (int) config('zakat.retention.purge_days', 30);
        $count = AiChatLog::where('created_at', '<', now()->subDays($purgeDays))->delete();

        $this->info("Berhasil menghapus {$count} baris log chatbot yang sudah berumur lebih dari {$purgeDays} hari.");

        return Command::SUCCESS;
    }
}

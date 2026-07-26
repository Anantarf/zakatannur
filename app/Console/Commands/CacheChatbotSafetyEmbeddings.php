<?php

namespace App\Console\Commands;

use App\Services\Chatbot\Safety\ChatbotSafetyEmbeddingsCache;
use Illuminate\Console\Command;

class CacheChatbotSafetyEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chatbot:cache-safety-embeddings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and cache OpenAI embeddings for the safety classifier dataset (run again after editing ChatbotSafetyDataset).';

    public function handle(ChatbotSafetyEmbeddingsCache $cache): int
    {
        $this->info('Generating safety dataset embeddings via OpenAI...');

        $embeddings = $cache->refreshCache();

        if (empty($embeddings)) {
            $this->error('Failed to generate embeddings. Check your OpenAI API key and internet connection.');

            return self::FAILURE;
        }

        $this->info('Successfully generated and cached embeddings for ' . count($embeddings) . ' safety dataset examples.');

        return self::SUCCESS;
    }
}

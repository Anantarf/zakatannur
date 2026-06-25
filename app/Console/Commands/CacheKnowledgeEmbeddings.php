<?php

namespace App\Console\Commands;

use App\Services\Chatbot\Knowledge\KnowledgeEmbeddingsCache;
use Illuminate\Console\Command;

class CacheKnowledgeEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chatbot:cache-embeddings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and cache OpenAI embeddings for the knowledge base';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(KnowledgeEmbeddingsCache $cache)
    {
        $this->info('Generating embeddings via OpenAI...');
        
        $embeddings = $cache->refreshCache();

        if (empty($embeddings)) {
            $this->error('Failed to generate embeddings. Check your OpenAI API key and internet connection.');
            return 1;
        }

        $this->info('Successfully generated and cached embeddings for ' . count($embeddings) . ' knowledge entries.');
        return 0;
    }
}

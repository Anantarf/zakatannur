<?php

namespace Tests\Feature;

use App\Services\Chatbot\Knowledge\ChatbotEvalDataset;
use App\Services\Chatbot\Knowledge\KnowledgeRetriever;
use Database\Seeders\KnowledgeBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotKnowledgeRetrievalEvalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression guard for KnowledgeRetriever's keyword-fallback path (the one guaranteed to run
     * whenever the embeddings API is unavailable). Catches "editing keywords/KB broke retrieval
     * for topic X" without needing a real embeddings API call - see chatbot:eval-rag for the
     * semantic-search counterpart, which does need one and is run manually.
     */
    public function test_keyword_fallback_routes_canonical_questions_to_the_expected_topic(): void
    {
        // No embeddings API call should happen at all here, but fake the network as a safety
        // net so a stray call can never hit the real API or slow the test down.
        Http::fake();

        (new KnowledgeBaseSeeder())->run();

        $retriever = $this->app->make(KnowledgeRetriever::class);
        $failures = [];

        foreach (ChatbotEvalDataset::cases() as $case) {
            $results = $retriever->search($case['question'], 3);
            $slugs = collect($results)->pluck('id')->all();

            if (!in_array($case['expected_slug'], $slugs, true)) {
                $failures[] = "\"{$case['question']}\" expected [{$case['expected_slug']}] in top-3, got [" . implode(', ', $slugs) . ']';
            }
        }

        $this->assertEmpty($failures, implode("\n", $failures));
    }
}

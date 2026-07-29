<?php

namespace Tests\Unit;

use App\Services\Chatbot\Providers\OpenAiEmbeddingsProvider;
use App\Services\Chatbot\Safety\ChatbotSafetyClassifier;
use App\Services\Chatbot\Safety\ChatbotSafetyEmbeddingsCache;
use Tests\TestCase;

class ChatbotSafetyClassifierTest extends TestCase
{
    private function classifier(): ChatbotSafetyClassifier
    {
        // classifyVector() is pure math (cosine similarity + threshold lookup) - no HTTP call
        // needed, so real collaborators are fine here even though they're never exercised.
        return new ChatbotSafetyClassifier(
            new OpenAiEmbeddingsProvider('', 'https://example.test'),
            new ChatbotSafetyEmbeddingsCache(new OpenAiEmbeddingsProvider('', 'https://example.test'))
        );
    }

    public function test_classify_vector_picks_nearest_reference_by_cosine_similarity(): void
    {
        // ChatbotSafetyDataset::cases()[0] is the first in_domain example, [46] is the first
        // out_of_scope example (46 in_domain cases precede it, Bab 18) - indices must line up
        // with a reference array shaped like the real cache for classifyVector() to resolve a
        // category. This index is inherently coupled to inDomainCases()'s count - if it drifts
        // out of sync again, this test starts asserting against the wrong category's cases.
        $reference = [
            0 => [1.0, 0.0, 0.0],
            46 => [0.0, 1.0, 0.0],
        ];

        $result = $this->classifier()->classifyVector([0.0, 1.0, 0.0], $reference);

        $this->assertNotNull($result);
        $this->assertSame('out_of_scope', $result['category']);
        $this->assertEqualsWithDelta(1.0, $result['score'], 0.0001);
        $this->assertSame('confident', $result['confidence']);
    }

    public function test_confidence_tiers_match_documented_thresholds(): void
    {
        // Reads the class constants rather than hardcoding their values, so this test can't
        // silently drift out of sync the next time `chatbot:eval-safety`'s threshold sweep
        // finds a better cut point and CONFIDENT_THRESHOLD/AMBIGUOUS_THRESHOLD get retuned.
        $confident = ChatbotSafetyClassifier::CONFIDENT_THRESHOLD;
        $ambiguous = ChatbotSafetyClassifier::AMBIGUOUS_THRESHOLD;

        $this->assertSame('confident', ChatbotSafetyClassifier::confidenceFor($confident));
        $this->assertSame('confident', ChatbotSafetyClassifier::confidenceFor(1.0));
        $this->assertSame('ambiguous', ChatbotSafetyClassifier::confidenceFor($confident - 0.01));
        $this->assertSame('ambiguous', ChatbotSafetyClassifier::confidenceFor($ambiguous));
        $this->assertSame('no_match', ChatbotSafetyClassifier::confidenceFor($ambiguous - 0.01));
        $this->assertSame('no_match', ChatbotSafetyClassifier::confidenceFor(0.0));
    }

    public function test_classify_vector_returns_null_for_empty_reference_set(): void
    {
        $this->assertNull($this->classifier()->classifyVector([1.0, 0.0], []));
    }

    public function test_classify_vector_resolves_in_domain_category_at_index_zero(): void
    {
        // Index 0 is ChatbotSafetyDataset's first in_domain example - a confident match there
        // must resolve to 'in_domain', which ChatbotSafetyClassifier::checkReply() deliberately
        // excludes from BLOCKABLE_CATEGORIES so legitimate zakat content never gets rejected.
        $reference = [0 => [1.0, 0.0]];
        $result = $this->classifier()->classifyVector([1.0, 0.0], $reference);

        $this->assertSame('in_domain', $result['category']);
    }
}

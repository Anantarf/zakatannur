<?php

namespace Tests\Unit;

use App\Services\Chatbot\ChatbotGuardrailVerifier;
use App\Services\Chatbot\ChatbotSentinelParser;
use App\Services\Chatbot\ChatbotStreamParser;
use Tests\TestCase;

class ChatbotStreamParserTest extends TestCase
{
    private function parser(): ChatbotStreamParser
    {
        return new ChatbotStreamParser(new ChatbotSentinelParser(), new ChatbotGuardrailVerifier());
    }

    public function test_plain_text_streams_through_as_sentences(): void
    {
        $parser = $this->parser();
        $sentences = iterator_to_array($parser->parse(['Halo. ', 'Apa kabar?']));

        $this->assertSame('Halo. Apa kabar?', implode('', $sentences));
        $this->assertSame('Halo. Apa kabar?', $parser->fullReply());
        $this->assertFalse($parser->guardrailTripped());
    }

    public function test_suggest_sentinel_is_swallowed_even_when_split_across_chunks(): void
    {
        $parser = $this->parser();
        $sentences = iterator_to_array($parser->parse(['Jawaban. [SUG', 'GEST: opsi]sisa teks.']));

        $this->assertSame('Jawaban. sisa teks.', implode('', $sentences));
        $this->assertStringContainsString('[SUGGEST: opsi]', $parser->fullReply());
    }

    public function test_guardrail_violation_stops_streaming(): void
    {
        $parser = $this->parser();
        $sentences = iterator_to_array($parser->parse(['resep masakan enak banget nih.']));

        $this->assertSame([], $sentences);
        $this->assertTrue($parser->guardrailTripped());
    }
}

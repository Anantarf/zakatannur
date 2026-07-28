<?php

namespace Tests\Unit;

use App\Services\Chatbot\ChatbotCitation;
use Tests\TestCase;

class ChatbotCitationTest extends TestCase
{
    public function test_from_knowledge_array_reads_source_label_not_label(): void
    {
        // KnowledgeBase::toKnowledgeArray() only ever exposes 'source_label' - this is the one
        // place that gets translated into the 'label' key the frontend actually reads
        // (chatbot-widget.blade.php: "'Acuan: ' + citations[0].label"). Bab 10.17's bug was this
        // translation missing entirely.
        $citation = ChatbotCitation::fromKnowledgeArray([
            'id' => 'zakat-properti-sewa',
            'source_label' => 'BAZNAS Daerah - Zakat Properti',
        ]);

        $this->assertSame('zakat-properti-sewa', $citation->id);
        $this->assertSame('BAZNAS Daerah - Zakat Properti', $citation->label);
    }

    public function test_from_knowledge_array_falls_back_when_source_label_missing(): void
    {
        $citation = ChatbotCitation::fromKnowledgeArray(['id' => 'x']);

        $this->assertSame('Panduan Zakat Masjid An-Nur', $citation->label);
    }

    public function test_to_array_shape_matches_what_the_frontend_reads(): void
    {
        $citation = new ChatbotCitation('x', 'Label X');

        $this->assertSame(['id' => 'x', 'label' => 'Label X'], $citation->toArray());
    }
}

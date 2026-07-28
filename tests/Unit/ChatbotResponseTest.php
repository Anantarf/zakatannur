<?php

namespace Tests\Unit;

use App\Services\Chatbot\ChatbotCitation;
use App\Services\Chatbot\ChatbotResponse;
use Tests\TestCase;

class ChatbotResponseTest extends TestCase
{
    public function test_actions_passed_to_constructor_are_not_silently_discarded(): void
    {
        // The constructor used to hardcode $this->actions = [] regardless of what was passed in -
        // no caller happened to rely on it yet, but it's a real correctness bug in the class
        // itself: the $actions parameter was accepted and then unconditionally ignored.
        $response = new ChatbotResponse('reply', 'action', [['type' => 'open_tab', 'target' => 'summary']]);

        $this->assertSame([['type' => 'open_tab', 'target' => 'summary']], $response->actions);
    }

    public function test_citations_serialize_with_the_label_key_the_frontend_reads(): void
    {
        $response = ChatbotResponse::success('reply', 'knowledge', [], [
            new ChatbotCitation('zakat-mal', 'BAZNAS - Zakat Mal'),
        ]);

        $this->assertSame(
            ['data' => ['citations' => [['id' => 'zakat-mal', 'label' => 'BAZNAS - Zakat Mal']]]],
            ['data' => ['citations' => $response->toArray()['data']['citations']]]
        );
    }
}

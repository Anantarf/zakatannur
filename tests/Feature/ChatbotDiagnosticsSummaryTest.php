<?php

namespace Tests\Feature;

use App\Services\Chatbot\ChatbotDiagnostics;
use Tests\TestCase;

class ChatbotDiagnosticsSummaryTest extends TestCase
{
    public function test_summary_reports_a_logged_layer_event(): void
    {
        ChatbotDiagnostics::warning(ChatbotDiagnostics::LAYER_GUARDRAIL, 'blocked_by_keyword', ['matched_keyword' => 'test']);

        // expectsOutputToContain() checks line-by-line and Symfony's table renderer wraps/pads
        // cell content, so a substring spanning column boundaries (like the full event name next
        // to its layer) isn't reliably matchable that way - assert on the layer name (which fits
        // its own cell untouched) and the exit code instead.
        $this->artisan('chatbot:diagnostics', ['--days' => 1])
            ->expectsOutputToContain('guardrail')
            ->assertExitCode(0);
    }
}

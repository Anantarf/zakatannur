<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ThrottleChatbotTest extends TestCase
{
    use RefreshDatabase;

    public function test_requests_under_the_limit_pass_through_with_a_remaining_count_header(): void
    {
        Cache::flush();

        $response = $this->postJson('/api/chatbot/message', ['message' => 'Halo']);

        $response->assertOk();
        $this->assertSame('49', $response->headers->get('X-RateLimit-Remaining'));
    }

    public function test_the_51st_request_within_a_minute_is_blocked_with_retry_headers(): void
    {
        // Regression guard for a real gap: the 429 response used to carry no Retry-After or
        // X-RateLimit-Remaining header at all - a client had no machine-readable way to know when
        // it was safe to retry, only the Indonesian text "Tunggu beberapa menit".
        Cache::flush();

        for ($i = 0; $i < 50; $i++) {
            $this->postJson('/api/chatbot/message', ['message' => 'Halo'])->assertOk();
        }

        $blocked = $this->postJson('/api/chatbot/message', ['message' => 'Halo']);

        $blocked->assertStatus(429)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('retryable', false);

        $this->assertSame('0', $blocked->headers->get('X-RateLimit-Remaining'));
        $this->assertGreaterThan(0, (int) $blocked->headers->get('Retry-After'));
    }
}

<?php

namespace Tests\Feature;

use App\Services\Chatbot\ChatbotServiceInterface;
use App\Services\Chatbot\Providers\GeminiChatbotProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_chatbot_returns_success_payload(): void
    {
        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Halo',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonPath('data.reply', 'Halo! Saya adalah asisten virtual Zakat An-Nur (versi simulasi). Ada yang bisa saya bantu terkait informasi zakat?');
    }

    public function test_chatbot_does_not_expose_internal_exception_messages(): void
    {
        $this->app->bind(ChatbotServiceInterface::class, fn () => new class implements ChatbotServiceInterface
        {
            public function sendMessage(string $message): string
            {
                throw new \RuntimeException('secret failure details');
            }
        });

        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Halo',
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Gagal memproses pesan. Silakan coba beberapa saat lagi.',
            ]);

        $this->assertStringNotContainsString('secret failure details', $response->getContent());
    }

    public function test_chatbot_returns_gemini_reply_when_provider_responds_ok(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[ 'content' => [ 'parts' => [[ 'text' => 'Halo dari Gemini' ]] ]]],
            ], 200),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new GeminiChatbotProvider(
            'test-key',
            'gemini-flash-latest',
            'https://generativelanguage.googleapis.com/v1beta/models'
        ));

        $response = $this->postJson('/api/chatbot/message', ['message' => 'halo']);

        $response->assertOk()
            ->assertJsonPath('data.reply', 'Halo dari Gemini');

        Http::assertSentCount(1);
    }

    public function test_chatbot_does_not_say_sibuk_when_model_missing(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'message' => 'models/x is not found for API version v1beta',
            ], 404),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new GeminiChatbotProvider(
            'test-key',
            'gemini-flash-latest',
            'https://generativelanguage.googleapis.com/v1beta/models'
        ));

        $response = $this->postJson('/api/chatbot/message', ['message' => 'halo']);

        $response->assertOk()
            ->assertJsonPath('data.reply', 'Mohon maaf, layanan asisten AI sedang tidak tersedia saat ini. Silakan coba beberapa saat lagi.');

        $this->assertStringNotContainsString('sibuk', $response->getContent());
    }
}

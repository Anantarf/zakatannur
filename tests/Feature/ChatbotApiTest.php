<?php

namespace Tests\Feature;

use App\Services\Chatbot\ChatbotServiceInterface;
use App\Services\Chatbot\Providers\GeminiChatbotProvider;
use App\Services\Chatbot\Providers\MockChatbotProvider;
use App\Models\Muzakki;
use App\Models\User;
use App\Models\ZakatTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_chatbot_returns_success_payload(): void
    {
        $this->app->bind(ChatbotServiceInterface::class, fn () => new MockChatbotProvider());

        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Halo',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonPath('data.reply', 'Halo! Saya Zakky, asisten virtual Zakat An-Nur (versi simulasi). Ada yang bisa saya bantu terkait informasi zakat?');
    }

    public function test_chatbot_does_not_expose_internal_exception_messages(): void
    {
        $this->app->bind(ChatbotServiceInterface::class, fn () => new class implements ChatbotServiceInterface
        {
            public function sendMessage(string $message, array $context = []): string
            {
                throw new \RuntimeException('secret failure details');
            }

            public function wasLastReplyFallback(): bool
            {
                return false;
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
                'candidates' => [[ 'content' => [ 'parts' => [[ 'text' => 'Halo, saya Zakky dari Gemini 2.5 Flash' ]] ]]],
            ], 200),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new GeminiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/models'
        ));

        $response = $this->postJson('/api/chatbot/message', ['message' => 'halo']);

        $response->assertOk()
            ->assertJsonPath('data.reply', 'Halo, saya Zakky dari Gemini 2.5 Flash');

        Http::assertSentCount(1);
    }

    public function test_chatbot_returns_503_when_gemini_model_missing(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'message' => 'models/x is not found for API version v1beta',
            ], 404),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new GeminiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/models'
        ));

        $response = $this->postJson('/api/chatbot/message', ['message' => 'halo']);

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'error',
                'retryable' => true,
            ]);

        $this->assertStringNotContainsString('sibuk', $response->getContent());
        $this->assertStringNotContainsString(ChatbotServiceInterface::FALLBACK_PREFIX, $response->getContent());
    }

    public function test_chatbot_returns_503_with_quota_message_when_gemini_rate_limited(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 429,
                    'message' => 'Quota exceeded',
                    'status' => 'RESOURCE_EXHAUSTED',
                ],
            ], 429),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new GeminiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/models'
        ));

        $response = $this->postJson('/api/chatbot/message', ['message' => 'halo']);

        $response->assertStatus(503)
            ->assertJsonPath('message', 'Kuota penggunaan AI harian sudah tercapai. Silakan coba lagi besok.');
    }

    public function test_chatbot_returns_503_when_gemini_returns_500(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => 'internal error',
            ], 500),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new GeminiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/models'
        ));

        $response = $this->postJson('/api/chatbot/message', ['message' => 'halo']);

        $response->assertStatus(503)
            ->assertJsonPath('retryable', true);
    }

    public function test_chatbot_falls_back_to_mock_when_no_provider_key(): void
    {
        config()->set('services.gemini.api_key', null);

        $this->app->forgetInstance(ChatbotServiceInterface::class);

        $response = $this->postJson('/api/chatbot/message', ['message' => 'halo']);

        $response->assertOk()
            ->assertJsonPath('data.reply', 'Halo! Saya Zakky, asisten virtual Zakat An-Nur (versi simulasi). Ada yang bisa saya bantu terkait informasi zakat?');
    }

    public function test_chatbot_returns_summary_action_without_ai_call(): void
    {
        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Buka ringkasan',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.source', 'action')
            ->assertJsonPath('data.actions.0.type', 'open_tab')
            ->assertJsonPath('data.actions.0.target', 'laporan');
    }

    public function test_chatbot_returns_chart_action_without_ai_call(): void
    {
        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Lihat grafik harian',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.source', 'action')
            ->assertJsonPath('data.actions.0.type', 'open_tab')
            ->assertJsonPath('data.actions.0.target', 'grafik');
    }

    public function test_chatbot_answers_payment_from_knowledge(): void
    {
        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Bagaimana cara bayar zakat?',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.source', 'knowledge')
            ->assertJsonPath('data.citations.0.label', 'Panduan Zakat Masjid An-Nur');
    }

    public function test_chatbot_answers_total_from_public_data(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => 'Ahmad']);

        ZakatTransaction::query()->create([
            'no_transaksi' => 'TRX-20260308-0001',
            'muzakki_id' => $muzakki->id,
            'pembayar_nama' => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => ZakatTransaction::SHIFTS[0],
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat' => 2026,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => 25000,
            'jumlah_beras_kg' => null,
            'jiwa' => 2,
            'hari' => null,
            'petugas_id' => $petugas->id,
            'status' => ZakatTransaction::STATUS_VALID,
            'waktu_terima' => now('Asia/Jakarta'),
        ]);

        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Berapa total penerimaan zakat saat ini?',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.source', 'public_data')
            ->assertJsonPath('data.actions.0.target', 'laporan');

        $this->assertStringContainsString('Rp 25.000', $response->json('data.reply'));
    }
}

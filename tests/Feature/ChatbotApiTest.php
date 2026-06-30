<?php

namespace Tests\Feature;

use App\Services\Chatbot\ChatbotServiceInterface;
use App\Services\Chatbot\Providers\OpenAiChatbotProvider;
use App\Services\Chatbot\Providers\MockChatbotProvider;
use App\Models\Muzakki;
use App\Models\User;
use App\Models\ZakatTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
            ->assertJsonPath('data.reply', 'Halo! Assalamualaikum. Saya Zakky, asisten virtual Zakat An-Nur. Ada yang bisa saya bantu terkait zakat, fidyah, atau operasional masjid hari ini?');
    }

    public function test_chatbot_does_not_expose_internal_exception_messages(): void
    {
        $this->app->bind(ChatbotServiceInterface::class, fn () => new class implements ChatbotServiceInterface
        {
            public function sendMessage(string $message, array $context = [], string $language = 'id', array $history = []): string
            {
                throw new \RuntimeException('secret failure details');
            }

            public function streamMessage(string $message, array $context = [], string $language = 'id', array $history = []): \Generator
            {
                throw new \RuntimeException('secret failure details');
                yield; // make it a Generator
            }

            public function wasLastReplyFallback(): bool
            {
                return false;
            }
        });

        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Apa itu reksa dana syariah di pasar modal global?',
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
                'choices' => [[ 'message' => [ 'content' => 'Halo, saya Zakky dari Gemini 2.5 Flash' ] ]],
            ], 200),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new OpenAiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/openai'
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

        $this->app->bind(ChatbotServiceInterface::class, fn () => new OpenAiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/openai'
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
                'error' => ['message' => 'Resource has been exhausted (e.g. check quota).'],
            ], 429),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new OpenAiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/openai'
        ));

        $response = $this->postJson('/api/chatbot/message', ['message' => 'halo']);

        $response->assertStatus(503)
            ->assertJsonPath('message', 'Terlalu banyak pertanyaan. Tunggu 1 menit, lalu coba lagi.');
    }

    public function test_chatbot_returns_503_when_gemini_returns_500(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => 'internal error',
            ], 500),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new OpenAiChatbotProvider(
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
            ->assertJsonPath('data.reply', 'Halo! Saya Zakky, asisten virtual Zakat An-Nur. Saya bisa bantu membaca ringkasan penerimaan, grafik, dan panduan umum zakat.');
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
            ->assertJsonPath('data.citations.0.label', 'Panduan Zakat Masjid An-Nur')
            ->assertJsonPath('data.actions.0.type', 'suggested_reply')
            ->assertJsonPath('data.actions.1.type', 'open_tab');
    }

    public function test_chatbot_answers_total_from_public_data(): void
    {
        Cache::flush();

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
            ->assertJsonPath('data.actions.0.target', 'laporan')
            ->assertJsonPath('data.actions.1.type', 'suggested_reply')
            ->assertJsonPath('data.context.topic', 'public_data');

        $this->assertStringContainsString('Rp 25.000', $response->json('data.reply'));
    }

    public function test_chatbot_uses_public_data_context_for_follow_up_question(): void
    {
        Cache::flush();

        $petugas = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => 'Ahmad']);

        ZakatTransaction::query()->create([
            'no_transaksi' => 'TRX-20260308-0006',
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
            'jiwa' => 1,
            'hari' => null,
            'petugas_id' => $petugas->id,
            'status' => ZakatTransaction::STATUS_VALID,
            'waktu_terima' => now('Asia/Jakarta'),
        ]);

        $first = $this->postJson('/api/chatbot/message', [
            'message' => 'Berapa total uang yang terkumpul?',
        ]);

        $followUp = $this->postJson('/api/chatbot/message', [
            'message' => 'kapan terakhir?',
            'context' => $first->json('data.context'),
        ]);

        $followUp->assertOk()
            ->assertJsonPath('data.source', 'public_data')
            ->assertJsonPath('data.context.last_intent', 'ask_latest_update');

        $this->assertStringContainsString('Data publik terakhir diperbarui', $followUp->json('data.reply'));
    }

    public function test_chatbot_answers_rice_total_from_public_data(): void
    {
        Cache::flush();

        $petugas = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => 'Ahmad']);

        ZakatTransaction::query()->create([
            'no_transaksi' => 'TRX-20260308-0002',
            'muzakki_id' => $muzakki->id,
            'pembayar_nama' => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => ZakatTransaction::SHIFTS[0],
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat' => 2026,
            'metode' => ZakatTransaction::METHOD_BERAS,
            'nominal_uang' => 0,
            'jumlah_beras_kg' => 2.5,
            'jiwa' => 1,
            'hari' => null,
            'petugas_id' => $petugas->id,
            'status' => ZakatTransaction::STATUS_VALID,
            'waktu_terima' => now('Asia/Jakarta'),
        ]);

        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Beras terkumpul berapa kg?',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.source', 'public_data');

        $this->assertStringContainsString('2,50 Kg', $response->json('data.reply'));
    }

    public function test_chatbot_answers_people_total_from_public_data(): void
    {
        Cache::flush();

        $petugas = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => 'Ahmad']);

        ZakatTransaction::query()->create([
            'no_transaksi' => 'TRX-20260308-0003',
            'muzakki_id' => $muzakki->id,
            'pembayar_nama' => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => ZakatTransaction::SHIFTS[0],
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat' => 2026,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => 50000,
            'jumlah_beras_kg' => null,
            'jiwa' => 3,
            'hari' => null,
            'petugas_id' => $petugas->id,
            'status' => ZakatTransaction::STATUS_VALID,
            'waktu_terima' => now('Asia/Jakarta'),
        ]);

        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Berapa total jiwa fitrah?',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.source', 'public_data');

        $this->assertStringContainsString('3 jiwa', $response->json('data.reply'));
    }

    public function test_chatbot_answers_categories_and_top_category(): void
    {
        Cache::flush();

        $petugas = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => 'Ahmad']);

        foreach ([
            [ZakatTransaction::CATEGORY_FITRAH, 25000, 'TRX-20260308-0004'],
            [ZakatTransaction::CATEGORY_INFAK, 100000, 'TRX-20260308-0005'],
        ] as [$category, $nominal, $number]) {
            ZakatTransaction::query()->create([
                'no_transaksi' => $number,
                'muzakki_id' => $muzakki->id,
                'pembayar_nama' => 'Hamba Allah',
                'pembayar_phone' => '0812',
                'pembayar_alamat' => 'Jakarta',
                'shift' => ZakatTransaction::SHIFTS[0],
                'category' => $category,
                'tahun_zakat' => 2026,
                'metode' => ZakatTransaction::METHOD_UANG,
                'nominal_uang' => $nominal,
                'jumlah_beras_kg' => null,
                'jiwa' => $category === ZakatTransaction::CATEGORY_FITRAH ? 1 : null,
                'hari' => null,
                'petugas_id' => $petugas->id,
                'status' => ZakatTransaction::STATUS_VALID,
                'waktu_terima' => now('Asia/Jakarta'),
            ]);
        }

        $categories = $this->postJson('/api/chatbot/message', ['message' => 'Kategori apa saja yang tercatat?']);
        $categories->assertOk()
            ->assertJsonPath('data.source', 'public_data');
        $this->assertStringContainsString('Fitrah', $categories->json('data.reply'));
        $this->assertStringContainsString('Infaq', $categories->json('data.reply'));

        $top = $this->postJson('/api/chatbot/message', ['message' => 'Kategori terbesar apa?']);
        $top->assertOk()
            ->assertJsonPath('data.source', 'public_data');
        $this->assertStringContainsString('Infaq', $top->json('data.reply'));
        $this->assertStringContainsString('Rp 100.000', $top->json('data.reply'));
    }

    public function test_chatbot_answers_latest_update_and_empty_data(): void
    {
        Cache::flush();

        $latest = $this->postJson('/api/chatbot/message', ['message' => 'Kapan data terakhir diperbarui?']);
        $latest->assertOk()
            ->assertJsonPath('data.source', 'public_data');
        $this->assertStringContainsString('Data publik terakhir diperbarui', $latest->json('data.reply'));

        $empty = $this->postJson('/api/chatbot/message', ['message' => 'Berapa total penerimaan zakat saat ini?']);
        $empty->assertOk()
            ->assertJsonPath('data.source', 'public_data');
        $this->assertStringContainsString('Belum ada data penerimaan', $empty->json('data.reply'));
    }
}

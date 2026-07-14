<?php

namespace Tests\Feature;

use App\Services\Chatbot\ChatbotOrchestrator;
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

        $response = $this->postJson('/api/chatbot/message', ['message' => 'Apa hukum zakat emas?']);

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

        $response = $this->postJson('/api/chatbot/message', ['message' => 'Apa hukum zakat emas?']);

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

        $response = $this->postJson('/api/chatbot/message', ['message' => 'Apa hukum zakat emas?']);

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

        $response = $this->postJson('/api/chatbot/message', ['message' => 'Apa hukum zakat emas?']);

        $response->assertStatus(503)
            ->assertJsonPath('retryable', true);
    }

    public function test_chatbot_falls_back_to_mock_when_no_provider_key(): void
    {
        config()->set('services.gemini.api_key', null);

        $this->app->forgetInstance(ChatbotServiceInterface::class);

        $response = $this->postJson('/api/chatbot/message', ['message' => 'Apa rekomendasi investasi emas?']);

        $response->assertOk()
            ->assertJsonPath('data.reply', 'Saya belum punya jawaban pasti untuk pertanyaan itu. Coba tanyakan total uang, total beras, total jiwa, kategori penerimaan, update terakhir, atau cara bayar zakat.');
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

    public function test_chatbot_computes_zakat_mal_from_hitung_sentinel_and_shows_inputs_used(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'choices' => [[ 'message' => [ 'content' => 'Oke, datanya lengkap. '
                    . '[HITUNG:{"income_monthly":10000000,"expenses_monthly":2000000,"savings":50000000,"gold_gram":0,"debt":0}]' ] ]],
            ], 200),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new OpenAiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/openai'
        ));

        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Cicilan rumah 2 juta sebulan, itungin dong',
        ]);

        $reply = $response->assertOk()->json('data.reply');

        // Inputs are echoed back so a misread number is visible to the user, not hidden inside the total.
        $this->assertStringContainsString('Penghasilan bulanan: Rp 10.000.000', $reply);
        $this->assertStringContainsString('Tabungan: Rp 50.000.000', $reply);
        $this->assertStringContainsString('Total aset kotor: Rp 170.000.000', $reply);
        $this->assertStringContainsString('Aset neto: Rp 146.000.000', $reply);
        $this->assertStringContainsString('Wajib zakat mal', $reply);
    }

    public function test_chatbot_rejects_implausible_hitung_amount_instead_of_computing(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'choices' => [[ 'message' => [ 'content' =>
                    '[HITUNG:{"income_monthly":999999999999,"savings":0}]' ] ]],
            ], 200),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new OpenAiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/openai'
        ));

        $response = $this->postJson('/api/chatbot/message', ['message' => 'Cicilan motor 2 juta sebulan, itungin dong ya']);

        $reply = $response->assertOk()->json('data.reply');

        $this->assertStringContainsString('kurang masuk akal', $reply);
        $this->assertStringNotContainsString('Wajib zakat mal', $reply);
    }

    public function test_chatbot_stream_blocks_off_topic_reply_before_yielding_the_violating_sentence(): void
    {
        // Chunks split the forbidden phrase ("resep masakan") across chunk boundaries, and the
        // violating sentence is the very first one — proving the guardrail check runs on the
        // accumulated reply (not per raw chunk) and blocks before that sentence is ever yielded.
        $chunks = ['Ini ', 'res', 'ep ', 'masakan ', 'rendang ', 'yang enak sekali.', ' Cara membuatnya mudah.'];

        $this->app->bind(ChatbotServiceInterface::class, fn () => new class ($chunks) implements ChatbotServiceInterface {
            public function __construct(private array $chunks)
            {
            }

            public function sendMessage(string $message, array $context = [], string $language = 'id', array $history = []): string
            {
                return implode('', $this->chunks);
            }

            public function streamMessage(string $message, array $context = [], string $language = 'id', array $history = []): \Generator
            {
                foreach ($this->chunks as $chunk) {
                    yield $chunk;
                }
            }

            public function wasLastReplyFallback(): bool
            {
                return false;
            }
        });

        $orchestrator = app(ChatbotOrchestrator::class);

        $yieldedChunks = [];
        $finalResponse = null;
        foreach ($orchestrator->stream('Kasih resep masakan dong', [], 'test-session') as $item) {
            if (isset($item['response'])) {
                $finalResponse = $item['response'];
            } elseif (isset($item['chunk'])) {
                $yieldedChunks[] = $item['chunk'];
            }
        }

        $this->assertSame([], $yieldedChunks, 'No chunk should reach the consumer once the guardrail trips.');
        $this->assertNotNull($finalResponse);
        $this->assertSame(403, $finalResponse->statusCode);
        $this->assertStringContainsString('asisten khusus Zakat An-Nur', $finalResponse->reply);
    }

    public function test_chatbot_sends_correction_hint_even_when_no_knowledge_context_matches(): void
    {
        // A short correction like this rarely matches any knowledge_bases entry semantically,
        // so the hint must not depend on the retrieved context being non-empty.
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'choices' => [[ 'message' => [ 'content' => 'Oke, saya catat perubahannya.' ] ]],
            ], 200),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new OpenAiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/openai'
        ));

        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'eh salah, harusnya 12 juta bukan 10 juta',
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) {
            $systemMessage = collect($request->data()['messages'])->firstWhere('role', 'system')['content'] ?? '';
            return str_contains($systemMessage, 'GANTI nilai lama itu dengan nilai baru');
        });
    }

    public function test_chatbot_guardrail_blocks_off_topic_reply(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'choices' => [[ 'message' => [ 'content' =>
                    'Ini bukan soal zakat, tapi kalau kamu mau resep masakan rendang, saya bisa bantu jelaskan bumbunya secara lengkap dari awal sampai akhir.' ] ]],
            ], 200),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new OpenAiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/openai'
        ));

        $response = $this->postJson('/api/chatbot/message', ['message' => 'Kasih resep masakan dong']);

        $response->assertStatus(403);
        $this->assertStringContainsString('asisten khusus Zakat An-Nur', $response->json('message'));
    }
}

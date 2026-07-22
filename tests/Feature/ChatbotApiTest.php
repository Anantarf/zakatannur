<?php

namespace Tests\Feature;

use App\Services\Chatbot\ChatbotOrchestrator;
use App\Services\Chatbot\ChatbotServiceInterface;
use App\Services\Chatbot\Providers\OpenAiChatbotProvider;
use App\Services\Chatbot\Providers\MockChatbotProvider;
use App\Models\AiChatLog;
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
            ->assertJsonPath('data.reply', 'Halo! Assalamualaikum. Saya Zakky. Ceritakan kebutuhan Anda, misalnya ingin hitung zakat fitrah, tanya zakat mal, cek fidyah, atau memahami cara pembayaran.');
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

            public function lastUsageMetadata(): array
            {
                return [];
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

    public function test_chatbot_answers_summary_request_without_opening_tab(): void
    {
        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Buka ringkasan',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.source', 'public_data')
            ->assertJsonPath('data.actions', []);
    }

    public function test_chatbot_answers_chart_request_without_opening_tab(): void
    {
        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Lihat grafik harian',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.source', 'public_data')
            ->assertJsonPath('data.actions', []);
    }

    public function test_chatbot_answers_payment_from_knowledge(): void
    {
        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Bagaimana cara bayar zakat?',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.source', 'knowledge')
            ->assertJsonPath('data.citations.0.label', 'Panduan Zakat Masjid An-Nur')
            ->assertJsonPath('data.actions', []);
    }

    public function test_chatbot_answers_nisab_from_nisab_knowledge_entry(): void
    {
        (new \Database\Seeders\KnowledgeBaseSeeder())->run();

        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Nisab itu apa sih?',
        ]);

        $reply = $response->assertOk()
            ->assertJsonPath('data.source', 'knowledge')
            ->assertJsonPath('data.citations.0.id', 'nisab-dan-haul')
            ->json('data.reply');

        $this->assertStringContainsString('85 gram', $reply);
    }

    public function test_chatbot_capability_question_is_not_hijacked_by_total_summary(): void
    {
        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'kamu seberapa jago emang bahas zakat?',
        ]);

        $reply = $response->assertOk()
            ->assertJsonPath('data.source', 'knowledge')
            ->assertJsonPath('data.citations.0.id', 'tentang-zakky')
            ->json('data.reply');

        $this->assertStringContainsString('zakat fitrah', $reply);
        $this->assertStringContainsString('zakat mal', $reply);
        $this->assertStringNotContainsString('Ringkasan penerimaan', $reply);
    }

    public function test_openai_provider_routes_fast_default_and_premium_models(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[ 'message' => [ 'content' => 'Jawaban model.' ] ]],
                'usage' => [
                    'prompt_tokens' => 100,
                    'completion_tokens' => 25,
                    'total_tokens' => 125,
                ],
            ], 200),
        ]);

        $provider = new OpenAiChatbotProvider(
            'test-key',
            'gpt-5.6-terra',
            'https://api.openai.com/v1',
            models: [
                'fast' => 'gpt-5.6-luna',
                'premium' => 'gpt-5.6-sol',
            ],
        );

        $provider->sendMessage('Halo');
        $provider->sendMessage('Jelaskan perbedaan zakat dan infaq untuk jamaah baru', [
            ['title' => 'Pengertian Zakat', 'answer' => 'Zakat wajib, infaq sukarela.'],
        ]);
        $provider->sendMessage('Saya mau hitung zakat mal dari gaji dan tabungan');

        Http::assertSentCount(3);
        Http::assertSent(fn ($request) => $request->data()['model'] === 'gpt-5.6-luna');
        Http::assertSent(fn ($request) => $request->data()['model'] === 'gpt-5.6-terra');
        Http::assertSent(fn ($request) => $request->data()['model'] === 'gpt-5.6-sol');
        Http::assertSent(fn ($request) => !array_key_exists('temperature', $request->data()));

        $this->assertSame([
            'model' => 'gpt-5.6-sol',
            'prompt_tokens' => 100,
            'completion_tokens' => 25,
            'total_tokens' => 125,
            'estimated_cost_usd' => 0.00125,
        ], $provider->lastUsageMetadata());
    }

    public function test_chatbot_logs_openai_token_usage_and_estimated_cost(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[ 'message' => [ 'content' => 'Zakat emas termasuk zakat mal dengan nisab 85 gram.' ] ]],
                'usage' => [
                    'prompt_tokens' => 1200,
                    'completion_tokens' => 200,
                    'total_tokens' => 1400,
                ],
            ], 200),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new OpenAiChatbotProvider(
            'test-key',
            'gpt-5.6-terra',
            'https://api.openai.com/v1',
            models: [
                'fast' => 'gpt-5.6-luna',
                'premium' => 'gpt-5.6-sol',
            ],
        ));

        $this->postJson('/api/chatbot/message', [
            'message' => 'Jelaskan zakat emas untuk konsultasi saya',
            'session_id' => 'cost-session',
        ])->assertOk();

        $log = AiChatLog::where('session_id', 'cost-session')->firstOrFail();

        $this->assertSame('gpt-5.6-sol', $log->model);
        $this->assertSame(1200, $log->prompt_tokens);
        $this->assertSame(200, $log->completion_tokens);
        $this->assertSame(1400, $log->total_tokens);
        $this->assertEquals(0.012, (float) $log->estimated_cost_usd);
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
            ->assertJsonPath('data.actions', [])
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
        // Income and savings are assessed against nisab separately (not pooled into one asset
        // base) - see ChatbotZakatMalGuide. Net income 96jt/year clears nisab (76,5jt); the 50jt
        // savings alone does not.
        $this->assertStringContainsString('Penghasilan bersih tahunan: Rp 96.000.000', $reply);
        $this->assertStringContainsString('wajib zakat penghasilan, sekitar Rp 2.400.000 per tahun', $reply);
        $this->assertStringContainsString('Aset simpanan (tabungan + emas - hutang): Rp 50.000.000', $reply);
        $this->assertStringContainsString('belum wajib zakat tabungan/emas', $reply);
        $this->assertStringContainsString('Total estimasi zakat: Rp 2.400.000 per tahun', $reply);
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
        $this->assertStringNotContainsString('wajib zakat penghasilan', $reply);
        $this->assertStringNotContainsString('wajib zakat tabungan', $reply);
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

            public function lastUsageMetadata(): array
            {
                return [];
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
        $this->assertStringContainsString('Saya bantu untuk topik zakat dan layanan Masjid An-Nur dulu ya', $finalResponse->reply);
    }

    public function test_chatbot_guardrail_does_not_block_legitimate_zakat_saham_reply(): void
    {
        // 'saham' used to sit in the guardrail's forbidden-topics list, which would have
        // wrongly blocked the AI answering from the (legitimate) zakat-saham-investasi-reksadana
        // knowledge base entry.
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'choices' => [[ 'message' => [ 'content' =>
                    'Zakat saham dan investasi termasuk pembahasan zakat mal kontemporer. '
                    . 'Objek zakatnya dapat berupa nilai kepemilikan, dividen, atau capital gain. '
                    . 'Kadar zakat yang umum digunakan adalah 2,5 persen.' ] ]],
            ], 200),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new OpenAiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/openai'
        ));

        $response = $this->postJson('/api/chatbot/message', ['message' => 'Bagaimana zakat saham dan investasi?']);

        $response->assertOk();
        $this->assertStringContainsString('Zakat saham', $response->json('data.reply'));
    }

    public function test_chatbot_guardrail_still_catches_off_topic_content_hidden_alongside_hitung_sentinel(): void
    {
        // The guardrail used to bail out of ALL checks the moment "[HITUNG:" appeared anywhere
        // in the reply, so off-topic content sharing a message with a sentinel slipped through.
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'choices' => [[ 'message' => [ 'content' =>
                    'Ini bukan soal zakat, tapi kalau kamu mau resep masakan rendang, saya bisa '
                    . 'bantu jelaskan bumbunya secara lengkap dari awal sampai akhir persiapan. '
                    . '[HITUNG:{"income_monthly":10000000}]' ] ]],
            ], 200),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new OpenAiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/openai'
        ));

        $response = $this->postJson('/api/chatbot/message', ['message' => 'Cicilan motor 2 juta sebulan, itungin dong ya']);

        $response->assertStatus(403);
        $this->assertStringContainsString('Saya bantu untuk topik zakat dan layanan Masjid An-Nur dulu ya', $response->json('message'));
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

    public function test_zakat_mal_consultation_mode_is_sent_to_provider_and_returned_as_context(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'choices' => [[ 'message' => [ 'content' =>
                    'Saya catat dulu datanya. Tabungan sudah ada, tinggal hutang dan emas kalau ada. '
                    . '[SUGGEST: Saya tidak punya hutang] [SUGGEST: Saya punya emas]' ] ]],
            ], 200),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new OpenAiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/openai'
        ));

        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Saya mau hitung zakat mal, tabungan saya 80 juta',
            'session_id' => 'consultation-session',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.context.last_source', 'ai')
            ->assertJsonPath('data.context.topic', 'zakat_mal')
            ->assertJsonPath('data.context.mode', 'zakat_mal_consultation');

        Http::assertSent(function ($request) {
            $systemMessage = collect($request->data()['messages'])->firstWhere('role', 'system')['content'] ?? '';

            return str_contains($systemMessage, 'Mode percakapan: konsultasi zakat mal')
                && str_contains($systemMessage, 'tanyakan hanya data penting yang belum ada');
        });
    }

    public function test_system_prompt_instructs_confirming_intent_before_collecting_financial_data(): void
    {
        // Regression guard for the "bot langsung interogasi data padahal user cuma menyebut
        // angka sambil lalu" bug - the fix lives entirely in prompt wording, so the only thing
        // testable without a real LLM call is that the instruction wasn't quietly dropped by a
        // later prompt edit. Actual model compliance is checked by `chatbot:eval-behavior`.
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'choices' => [[ 'message' => [ 'content' => 'Boleh, mau saya bantu hitungkan estimasi zakat mal-nya?' ] ]],
            ], 200),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new OpenAiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/openai'
        ));

        $this->postJson('/api/chatbot/message', [
            'message' => 'Btw gaji saya bulan ini 7,5 juta, lumayan buat nabung.',
            'session_id' => 'confirm-intent-session',
        ])->assertOk();

        Http::assertSent(function ($request) {
            $systemMessage = collect($request->data()['messages'])->firstWhere('role', 'system')['content'] ?? '';

            return str_contains($systemMessage, 'Jangan langsung menganggap user mau dihitungkan zakat mal')
                && str_contains($systemMessage, 'Konfirmasi dulu niatnya');
        });
    }

    public function test_switching_topic_mid_consultation_leaves_zakat_mal_consultation_mode(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'choices' => [[ 'message' => [ 'content' => 'Zakat fitrah tahun ini diterima selama Ramadan sampai sebelum salat Idulfitri.' ] ]],
            ], 200),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new OpenAiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/openai'
        ));

        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Btw jadwal buka zakat fitrah kapan ya?',
            'session_id' => 'consultation-session-2',
            'context' => ['mode' => 'zakat_mal_consultation', 'last_source' => 'ai', 'topic' => 'zakat_mal'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.context.mode', 'general');

        Http::assertSent(function ($request) {
            $systemMessage = collect($request->data()['messages'])->firstWhere('role', 'system')['content'] ?? '';

            return !str_contains($systemMessage, 'Mode percakapan: konsultasi zakat mal');
        });
    }

    public function test_chatbot_sends_last_eight_conversation_turns_to_provider(): void
    {
        for ($i = 1; $i <= 9; $i++) {
            AiChatLog::query()->create([
                'session_id' => 'history-session',
                'question_md5' => md5('Pertanyaan ' . $i),
                'question' => 'Pertanyaan ' . $i,
                'answer' => 'Jawaban ' . $i,
                'source_type' => 'ai',
            ]);
        }

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'choices' => [[ 'message' => [ 'content' => 'Baik, saya lanjutkan konsultasinya.' ] ]],
            ], 200),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new OpenAiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/openai'
        ));

        $this->postJson('/api/chatbot/message', [
            'message' => 'Lanjutkan hitung zakat mal saya',
            'session_id' => 'history-session',
            'context' => ['mode' => 'zakat_mal_consultation', 'last_source' => 'ai'],
        ])->assertOk();

        Http::assertSent(function ($request) {
            $messages = collect($request->data()['messages']);
            $contents = $messages->pluck('content')->all();

            return !in_array('Pertanyaan 1', $contents, true)
                && in_array('Pertanyaan 2', $contents, true)
                && in_array('Jawaban 9', $contents, true)
                && $messages->where('role', 'user')->count() === 9;
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
        $this->assertStringContainsString('Saya bantu untuk topik zakat dan layanan Masjid An-Nur dulu ya', $response->json('message'));
    }

    public function test_ai_conversation_reply_is_not_hijacked_by_quick_response_keyword_match(): void
    {
        // Reproduces the reported bug: mid zakat-peternakan consultation, the AI asks
        // "kambingnya berapa ekor?" and the user answers with a message that legitimately
        // contains "berapa" ("...sudah berapa lama, dipakai untuk ternak atau dijual?").
        // Previously that reply got hijacked by ChatbotActionDetector's bare-'berapa'
        // fallback into an unrelated public-data summary instead of reaching the AI.
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'choices' => [[ 'message' => [ 'content' =>
                    'Baik, sudah saya catat jumlah kambingnya. Zakat peternakan dihitung dari jumlah dan lama pemeliharaan.' ] ]],
            ], 200),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new OpenAiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/openai'
        ));

        $orchestrator = app(ChatbotOrchestrator::class);

        // The context an AI-sourced reply carries on its way back to the frontend, and that
        // the frontend echoes back on the next request (see ChatbotStreamController + the
        // 'data.context' handler in chatbot-widget.js for the streaming equivalent of this).
        $aiConversationContext = ['last_source' => 'ai', 'topic' => 'ai_conversation'];

        $response = $orchestrator->handle(
            'Kambingnya berapa ekor? Sudah dipelihara berapa lama? Dipakai untuk ternak atau dijual?',
            $aiConversationContext,
            'test-session'
        );

        $this->assertStringContainsString('Zakat peternakan dihitung', $response->reply);
        $this->assertStringNotContainsString('Ringkasan penerimaan', $response->reply);
        $this->assertSame('ai', $response->source);

        Http::assertSentCount(1);
    }

    public function test_suggest_tags_are_stripped_instead_of_becoming_quick_replies(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'choices' => [[ 'message' => [ 'content' =>
                    'Baik, boleh saya tahu datanya? '
                    . '[SUGGEST: Berapa gaji bulanan Anda? | Ada tabungan atau simpanan berapa? | Punya emas atau hutang yang perlu dihitung?]'
                    . '[SUGGEST: Cara bayar zakat]' ] ]],
            ], 200),
        ]);

        $this->app->bind(ChatbotServiceInterface::class, fn () => new OpenAiChatbotProvider(
            'test-key',
            'gemini-2.5-flash',
            'https://generativelanguage.googleapis.com/v1beta/openai'
        ));

        $response = $this->postJson('/api/chatbot/message', ['message' => 'Bantu saya hitung zakat mal ternak']);

        $response->assertOk()
            ->assertJsonPath('data.actions', []);
        $this->assertSame('Baik, boleh saya tahu datanya?', $response->json('data.reply'));
    }
}

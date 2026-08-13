# Lampiran A - Potongan Kode Program Utama AI Assistant Zakky

Lampiran ini memuat potongan kode inti dari AI Assistant Zakky pada Sistem Informasi Zakat Masjid An-Nur. Potongan kode dipilih untuk menunjukkan alur utama sistem, mulai dari penerimaan pesan pengguna, orkestrasi chatbot, retrieval basis pengetahuan, kalkulasi deterministik, parsing sentinel, hingga mekanisme pengendalian respons.

Kode yang ditampilkan bukan seluruh repository, melainkan bagian utama yang paling merepresentasikan implementasi AI Assistant Zakky.

## A.1 ChatbotController

Lokasi file:

```text
app/Http/Controllers/Api/ChatbotController.php
```

Bagian ini bertugas menerima request dari pengguna, melakukan validasi input, membentuk session identifier, meneruskan pesan ke `ChatbotOrchestrator`, dan mengembalikan respons dalam format JSON.

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Chatbot\ChatbotOrchestrator;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    protected ChatbotOrchestrator $chatbot;

    public function __construct(ChatbotOrchestrator $chatbot)
    {
        $this->chatbot = $chatbot;
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'sometimes|required_unless:type,feedback|string|min:1|max:500',
            'context' => 'sometimes|array',
            'context.last_intent' => 'sometimes|string|max:80',
            'context.last_source' => 'sometimes|string|max:40',
            'context.topic' => 'sometimes|string|max:40',
            'context.mode' => 'sometimes|string|max:80',
            'session_id' => 'sometimes|string|max:100',
            'type' => 'sometimes|string|in:message,feedback',
            'feedback' => 'sometimes|required_if:type,feedback|string|in:helpful,unhelpful',
        ]);

        try {
            $type = $request->input('type', 'message');

            if ($type === 'feedback') {
                return $this->handleFeedback($request);
            }

            $message = trim($request->input('message'));
            if (strlen($message) < 1 || strlen($message) > 500) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pesan tidak valid. Gunakan 1-500 karakter.',
                    'retryable' => false,
                ], 400);
            }

            $sessionId = $request->input('session_id')
                ?: hash('sha256', $request->ip() . '|' . (string) $request->userAgent());
            $context = $request->input('context', []);

            $response = $this->chatbot->handle($message, $context, $sessionId);

            return response()->json($response->toArray(), $response->statusCode);
        } catch (\Throwable $e) {
            Log::error('Chatbot error', [
                'exception' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Layanan sementara tidak tersedia. Coba lagi nanti.',
                'retryable' => true,
            ], 503);
        }
    }
}
```

## A.2 ChatbotOrchestrator

Lokasi file:

```text
app/Services/Chatbot/ChatbotOrchestrator.php
```

`ChatbotOrchestrator` merupakan pusat alur kerja Zakky. Class ini menentukan apakah pertanyaan dapat dijawab melalui jalur cepat berbasis aturan atau harus diteruskan ke jalur AI dengan retrieval, provider LLM, sentinel parser, guardrail, dan safety classifier.

### A.2.1 Dependency utama orchestrator

```php
class ChatbotOrchestrator
{
    public function __construct(
        private ChatbotServiceInterface $aiProvider,
        private ChatbotActionDetector $actionDetector,
        private KnowledgeRetriever $knowledgeRetriever,
        private ChatbotPublicDataResponder $publicDataResponder,
        private ChatbotGuardrailVerifier $guardrailVerifier,
        private ChatbotSafetyClassifier $safetyClassifier,
        private ChatbotLanguageDetector $languageDetector,
        private ChatbotSentimentDetector $sentimentDetector,
        private ChatbotCalculatorService $calculatorService,
        private ChatbotSentinelParser $sentinelParser,
        private ChatbotChatLogger $chatLogger,
        private ChatbotConversationContext $conversationContext
    ) {
    }
}
```

### A.2.2 Method utama pemrosesan pesan

```php
public function handle(string $message, array $rawContext = [], ?string $sessionId = null): ChatbotResponse
{
    $startedAt = microtime(true);

    $quickResponse = $this->getQuickResponse($message, $rawContext, $sessionId);
    if ($quickResponse) {
        ChatbotDiagnostics::info(ChatbotDiagnostics::LAYER_ORCHESTRATOR, 'handled_fast_path', [
            'session_id' => $sessionId,
            'source' => $quickResponse->source,
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
        ]);

        return $quickResponse;
    }

    try {
        $sentiment = $this->sentimentDetector->detect($message);
        $response = $this->answerFromAi($message, $rawContext, $sessionId);
        $confidenceSource = $this->aiProvider->wasLastReplyFallback() ? 'fallback' : 'ai';

        $this->chatLogger->save(
            $message,
            null,
            $response->source,
            $response->reply,
            $sessionId,
            $sentiment,
            $confidenceSource,
            $this->aiProvider->lastUsageMetadata()
        );

        ChatbotDiagnostics::info(ChatbotDiagnostics::LAYER_ORCHESTRATOR, 'handled_ai_path', [
            'session_id' => $sessionId,
            'source' => $response->source,
            'confidence_source' => $confidenceSource,
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
        ]);

        return $response;
    } catch (Throwable $e) {
        ChatbotDiagnostics::error(ChatbotDiagnostics::LAYER_ORCHESTRATOR, 'unhandled_exception', [
            'session_id' => $sessionId,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile() . ':' . $e->getLine(),
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
        ]);

        Log::error('Chatbot orchestration failed.', [
            'message' => $e->getMessage(),
        ]);

        return ChatbotResponse::error('Gagal memproses pesan. Silakan coba beberapa saat lagi.', true, 500);
    }
}
```

### A.2.3 Jalur cepat berbasis aturan

```php
private function getQuickResponse(string $message, array $rawContext = [], ?string $sessionId = null): ?ChatbotResponse
{
    $cached = Cache::get($this->conversationContext->cacheKey($message, $rawContext, $sessionId));
    if ($cached) {
        $this->chatLogger->save($message, 'cached', $cached->source ?? 'cache', $cached->reply, $sessionId);
        return $cached;
    }

    $context = $this->conversationContext->parse($rawContext);

    if (($context['last_source'] ?? null) === 'ai') {
        return null;
    }

    try {
        $intent = $this->actionDetector->intent($message, $context);

        if ($intent === 'calculate_fitrah_case') {
            return $this->finalizeQuickResponse(
                $this->calculatorService->calculateFitrah($message),
                $message,
                $rawContext,
                $intent,
                'calculation',
                $sessionId
            );
        }

        if ($intent === 'calculate_fidyah_case') {
            return $this->finalizeQuickResponse(
                $this->calculatorService->calculateFidyah($message),
                $message,
                $rawContext,
                $intent,
                'calculation',
                $sessionId
            );
        }

        $publicData = $intent ? $this->publicDataResponder->respond($intent) : null;
        if ($publicData) {
            $response = $publicData->withContext(
                $this->conversationContext->forIntent($intent, 'public_data')
            );

            return $this->finalizeQuickResponse(
                $response,
                $message,
                $rawContext,
                $intent,
                'public_data',
                $sessionId,
                'knowledge'
            );
        }

        $action = $this->actionDetector->detect($message);
        if ($action) {
            $response = $action->withContext(
                $this->conversationContext->forIntent($intent ?? 'chatbot_info', $action->source)
            );

            return $this->finalizeQuickResponse(
                $response,
                $message,
                $rawContext,
                $intent ?? 'chatbot_info',
                $action->source,
                $sessionId,
                null
            );
        }

        return null;
    } catch (Throwable $e) {
        ChatbotDiagnostics::error(ChatbotDiagnostics::LAYER_ACTION_DETECTOR, 'unhandled_exception', [
            'session_id' => $sessionId,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile() . ':' . $e->getLine(),
        ]);

        return ChatbotResponse::error('Gagal memproses pesan. Silakan coba beberapa saat lagi.', true, 500);
    }
}
```

### A.2.4 Jalur AI, retrieval, sentinel, dan guardrail

```php
private function answerFromAi(string $message, array $rawContext, ?string $sessionId): ChatbotResponse
{
    $language = $this->languageDetector->detect($message);
    $sentiment = $this->sentimentDetector->detect($message);
    $mode = $this->conversationContext->detectMode($message, $rawContext);

    $contexts = $this->conversationContext->withHints(
        $this->retrieveContexts($message, $rawContext, $mode),
        $message,
        $sentiment,
        $mode
    );

    $history = $this->chatLogger->history($sessionId);

    $reply = $this->aiProvider->sendMessage($message, $contexts, $language, $history);
    $wasFallback = $this->aiProvider->wasLastReplyFallback();

    return $this->finalizeAiReply($reply, $wasFallback, $contexts, $mode);
}

private function finalizeAiReply(string $rawReply, bool $wasFallback, array $contexts, string $mode): ChatbotResponse
{
    $cleanReply = $wasFallback && str_starts_with($rawReply, ChatbotServiceInterface::FALLBACK_PREFIX)
        ? substr($rawReply, strlen(ChatbotServiceInterface::FALLBACK_PREFIX))
        : $rawReply;

    if (!$wasFallback) {
        $cleanReply = $this->polishReply($cleanReply);
    }

    $cleanReply = $this->sentinelParser->parseAndCalculateSentinel($cleanReply);
    $cleanReply = $this->polishReply($cleanReply);

    $guardrailViolation = $this->guardrailVerifier->verify($cleanReply, $mode);
    if ($guardrailViolation !== null) {
        $cleanReply = $guardrailViolation;
        return ChatbotResponse::error($cleanReply, false, 403);
    }

    if (!$wasFallback) {
        $safetyViolation = $this->safetyClassifier->checkReply($cleanReply);
        if ($safetyViolation !== null) {
            return ChatbotResponse::error($safetyViolation, false, 403);
        }
    }

    return $wasFallback
        ? ChatbotResponse::error($cleanReply, true)
        : ChatbotResponse::success($cleanReply, 'ai', [], $this->buildCitations($contexts))
            ->withContext($this->conversationContext->aiContext($mode));
}
```

### A.2.5 Pengambilan konteks basis pengetahuan

```php
private function retrieveContexts(string $message, array $rawContext, string $mode): array
{
    $wasAlreadyConsulting = ($rawContext['mode'] ?? null) === 'zakat_mal_consultation';

    $looksLikeTangentQuestion = preg_match(
        '/[?]|\b(apa|apa itu|kenapa|gimana|bagaimana|kapan|dimana|di mana|siapa|jelasin|jelaskan)\b/i',
        $message
    ) === 1;

    if (
        $mode === 'zakat_mal_consultation'
        && $wasAlreadyConsulting
        && str_word_count($message) <= 8
        && !$looksLikeTangentQuestion
    ) {
        return [];
    }

    return $this->knowledgeRetriever->search($message, 3);
}
```

## A.3 KnowledgeRetriever

Lokasi file:

```text
app/Services/Chatbot/Knowledge/KnowledgeRetriever.php
```

Komponen ini menjalankan mekanisme RAG. Sistem mengambil entri aktif dari basis pengetahuan, mencoba pencarian semantik berbasis embedding dan cosine similarity, lalu menggunakan pencarian kata kunci sebagai fallback jika pencarian semantik tidak menghasilkan konteks.

```php
namespace App\Services\Chatbot\Knowledge;

use App\Services\Chatbot\ChatbotDiagnostics;
use App\Services\Chatbot\Providers\OpenAiEmbeddingsProvider;

class KnowledgeRetriever
{
    private const GENERIC_TITLE_WORDS = [
        'yang', 'dan', 'atau', 'untuk', 'dengan', 'dari', 'akan', 'bisa', 'ini', 'itu',
        'saya', 'anda', 'apa', 'siapa', 'kapan', 'dimana', 'bagaimana', 'gimana',
        'cara', 'jadwal', 'pada', 'oleh', 'jika', 'kalau', 'juga', 'saja',
    ];

    private OpenAiEmbeddingsProvider $embeddingsProvider;
    private KnowledgeEmbeddingsCache $embeddingsCache;

    public function __construct(OpenAiEmbeddingsProvider $embeddingsProvider, KnowledgeEmbeddingsCache $embeddingsCache)
    {
        $this->embeddingsProvider = $embeddingsProvider;
        $this->embeddingsCache = $embeddingsCache;
    }

    public function search(string $message, int $limit = 3, float $threshold = 0.45): array
    {
        $entries = \App\Models\KnowledgeBase::active()->get()->map->toKnowledgeArray()->all();

        $rankedViaSemantic = $this->searchViaEmbeddings($message, $entries, $threshold);

        if (!empty($rankedViaSemantic)) {
            return array_slice($rankedViaSemantic, 0, $limit);
        }

        ChatbotDiagnostics::warning(ChatbotDiagnostics::LAYER_KNOWLEDGE_RETRIEVER, 'fell_back_to_keyword_search', [
            'message_length' => mb_strlen($message),
        ]);

        return $this->searchViaKeywords($message, $entries, $limit);
    }
}
```

### A.3.1 Semantic search berbasis embedding

```php
private function searchViaEmbeddings(string $message, array $entries, float $threshold = 0.45): array
{
    if (empty(trim($message))) {
        ChatbotDiagnostics::warning(ChatbotDiagnostics::LAYER_KNOWLEDGE_RETRIEVER, 'empty_message_for_semantic_search');
        return [];
    }

    $messageEmbedding = $this->embeddingsProvider->getEmbedding($message);
    if (!$messageEmbedding) {
        ChatbotDiagnostics::warning(ChatbotDiagnostics::LAYER_KNOWLEDGE_RETRIEVER, 'embedding_generation_failed', [
            'message_length' => mb_strlen($message),
        ]);
        return [];
    }

    $knowledgeEmbeddings = $this->embeddingsCache->getCachedEmbeddings();
    if (empty($knowledgeEmbeddings)) {
        ChatbotDiagnostics::warning(ChatbotDiagnostics::LAYER_KNOWLEDGE_RETRIEVER, 'no_cached_embeddings_available');
        return [];
    }

    $ranked = [];
    foreach ($entries as $entry) {
        $entryId = $entry['id'] ?? null;
        if (!$entryId || !isset($knowledgeEmbeddings[$entryId])) {
            continue;
        }

        $similarity = KnowledgeEmbeddingsCache::cosineSimilarity(
            $messageEmbedding,
            $knowledgeEmbeddings[$entryId]
        );

        if ($similarity >= $threshold) {
            $entry['_cosine_similarity'] = $similarity;
            $ranked[] = $entry;
        }
    }

    usort($ranked, fn ($a, $b) => $b['_cosine_similarity'] <=> $a['_cosine_similarity']);

    return $ranked;
}
```

### A.3.2 Keyword fallback

```php
private function searchViaKeywords(string $message, array $entries, int $limit): array
{
    $message = $this->normalize($message);
    $ranked = [];

    foreach ($entries as $entry) {
        $score = $this->score($message, $entry);
        if ($score <= 0) {
            continue;
        }

        $entry['_score'] = $score;
        $ranked[] = $entry;
    }

    usort($ranked, fn ($a, $b) => $b['_score'] <=> $a['_score']);

    return array_slice($ranked, 0, $limit);
}

private function score(string $message, array $entry): int
{
    $score = 0;

    foreach (($entry['keywords'] ?? []) as $keyword) {
        $keyword = $this->normalize($keyword);
        if ($keyword === '' || !$this->containsWholeWord($message, $keyword)) {
            continue;
        }

        $isMultiWord = str_contains($keyword, ' ');
        if (!$isMultiWord && mb_strlen($keyword) < 4) {
            continue;
        }

        $score += $isMultiWord ? 5 : 3;
    }

    foreach (explode(' ', $this->normalize((string) ($entry['title'] ?? ''))) as $token) {
        if (
            mb_strlen($token) >= 4
            && !in_array($token, self::GENERIC_TITLE_WORDS, true)
            && $this->containsWholeWord($message, $token)
        ) {
            $score += 1;
        }
    }

    return $score;
}
```

## A.4 ChatbotCalculatorService

Lokasi file:

```text
app/Services/Chatbot/ChatbotCalculatorService.php
```

Komponen ini digunakan untuk kalkulasi cepat yang bersifat deterministik, seperti zakat fitrah dan fidyah. Perhitungan dilakukan oleh backend, bukan oleh model AI.

```php
namespace App\Services\Chatbot;

class ChatbotCalculatorService
{
    private const MAX_PLAUSIBLE_COUNT = 1000;

    public function calculateFitrah(string $message): ChatbotResponse
    {
        $count = $this->extractNumberFromText($message, ['orang', 'jiwa', 'person']);

        if (!$count) {
            return ChatbotResponse::success(
                'Berapa orang yang mau dihitung fitrahnya? Coba ketik: "Fitrah 4 orang berapa?"',
                'knowledge'
            );
        }

        $cashPerJiwa = config('zakat.annual_defaults.fitrah_cash_per_jiwa', 50000);
        $berasPerJiwa = config('zakat.annual_defaults.fitrah_beras_per_jiwa', 2.5);

        $totalCash = $count * $cashPerJiwa;
        $totalBeras = $count * $berasPerJiwa;

        $reply = sprintf(
            "Fitrah untuk %d orang:\n\n"
            . "Uang  : %d x Rp %s = Rp %s\n"
            . "Beras : %d x %.1f kg = %.1f kg\n\n"
            . "Angka ini mengacu tarif An-Nur tahun ini. Konfirmasi ke panitia sebelum bayar ya.",
            $count,
            $count,
            number_format($cashPerJiwa, 0, ',', '.'),
            number_format($totalCash, 0, ',', '.'),
            $count,
            $berasPerJiwa,
            $totalBeras
        );

        return ChatbotResponse::success($reply, 'calculation');
    }

    public function calculateFidyah(string $message): ChatbotResponse
    {
        $days = $this->extractNumberFromText($message, ['hari', 'day']);

        if (!$days) {
            return ChatbotResponse::success(
                'Berapa hari fidyahnya? Coba ketik: "Fidyah 7 hari berapa?"',
                'knowledge'
            );
        }

        $cashPerHari = config('zakat.annual_defaults.fidyah_per_hari', 30000);
        $berasPerHari = config('zakat.annual_defaults.fidyah_beras_per_hari', 0.75);

        $totalCash = $days * $cashPerHari;
        $totalBeras = $days * $berasPerHari;

        $reply = sprintf(
            "Fidyah untuk %d hari:\n\n"
            . "Uang  : %d x Rp %s = Rp %s\n"
            . "Beras : %d x %.2f kg = %.2f kg\n\n"
            . "Angka ini mengacu tarif An-Nur tahun ini. Konfirmasi ke panitia sebelum bayar ya.",
            $days,
            $days,
            number_format($cashPerHari, 0, ',', '.'),
            number_format($totalCash, 0, ',', '.'),
            $days,
            $berasPerHari,
            $totalBeras
        );

        return ChatbotResponse::success($reply, 'calculation');
    }
}
```

### A.4.1 Ekstraksi angka dari teks pengguna

```php
private function extractNumberFromText(string $text, array $keywords): ?int
{
    $normalized = strtolower($text);

    foreach ($keywords as $keyword) {
        $quotedKeyword = preg_quote($keyword, '/');
        $matched = preg_match('/(\d+)\s*' . $quotedKeyword . '/i', $normalized, $matches)
            || preg_match('/' . $quotedKeyword . '(?:\s+\w+){0,2}?\s+(\d+)\b/i', $normalized, $matches);

        if ($matched) {
            $count = (int) $matches[1];
            if ($count > 0 && $count <= self::MAX_PLAUSIBLE_COUNT) {
                return $count;
            }

            return null;
        }
    }

    $map = [
        'satu' => 1,
        'dua' => 2,
        'tiga' => 3,
        'empat' => 4,
        'lima' => 5,
        'enam' => 6,
        'tujuh' => 7,
        'delapan' => 8,
        'sembilan' => 9,
        'sepuluh' => 10,
        'sebelas' => 11,
        'dua belas' => 12,
    ];

    foreach ($keywords as $keyword) {
        foreach ($map as $word => $num) {
            if (preg_match('/' . preg_quote($word) . '[\s]*' . preg_quote($keyword) . '/i', $normalized)) {
                return $num;
            }
        }
    }

    return null;
}
```

## A.5 ChatbotSentinelParser

Lokasi file:

```text
app/Services/Chatbot/ChatbotSentinelParser.php
```

Komponen ini digunakan agar model AI tidak menghitung nominal zakat mal secara langsung. Model hanya mengeluarkan tag internal `[HITUNG:{...}]`, kemudian backend membaca tag tersebut, memvalidasi data, dan menjalankan kalkulasi deterministik.

```php
namespace App\Services\Chatbot;

class ChatbotSentinelParser
{
    public function parseAndCalculateSentinel(string $reply): string
    {
        $reply = preg_replace_callback('/\[HITUNG:\s*(\{.*?\})\s*\]/is', function (array $matches) {
            return $this->calculateSentinel($matches[1]);
        }, $reply);

        return trim($reply);
    }
}
```

### A.5.1 Validasi data sentinel

```php
private function calculateSentinel(string $jsonStr): string
{
    $data = json_decode($jsonStr, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        ChatbotDiagnostics::warning(ChatbotDiagnostics::LAYER_SENTINEL_PARSER, 'malformed_json', [
            'json_error' => json_last_error_msg(),
        ]);

        return "\n\n(Mohon maaf, saya kurang mengerti datanya. Bisa sebutkan nominal penghasilan bulanan, tabungan, dan emas yang dimiliki?)";
    }

    $year = (int) \App\Models\AppSetting::getInt(
        \App\Models\AppSetting::KEY_ACTIVE_YEAR,
        (int) now()->year
    );

    $defaultsResolver = app(\App\Services\Transactions\AnnualZakatDefaultsResolver::class);
    $defaults = $defaultsResolver->resolve($year);

    $maxPlausibleValue = $defaults->nishabAnnual() * 1000;

    $hasNegative = false;
    $hasImplausible = false;
    $hasNonNumeric = false;
    $allEmpty = true;

    foreach (['income_monthly', 'savings', 'gold_gram', 'debt'] as $key) {
        if (isset($data[$key])) {
            $allEmpty = false;

            if (!is_numeric($data[$key])) {
                $hasNonNumeric = true;
                continue;
            }

            $value = (int) $data[$key];
            if ($value < 0) {
                $hasNegative = true;
            } elseif ($value > $maxPlausibleValue) {
                $hasImplausible = true;
            }
        }
    }

    $hasIncomeData = isset($data['income_monthly']);
    $hasWealthData = (isset($data['savings']) && (int) $data['savings'] > 0)
        || (isset($data['gold_gram']) && (int) $data['gold_gram'] > 0);

    if ($hasNonNumeric) {
        ChatbotDiagnostics::warning(ChatbotDiagnostics::LAYER_SENTINEL_PARSER, 'rejected_non_numeric_value', [
            'data' => $data,
        ]);

        return "\n\n(Mohon maaf, saya kurang mengerti datanya. Bisa sebutkan nominal penghasilan bulanan, tabungan, dan emas yang dimiliki?)";
    }

    if ($hasNegative) {
        ChatbotDiagnostics::warning(ChatbotDiagnostics::LAYER_SENTINEL_PARSER, 'rejected_negative_value', [
            'data' => $data,
        ]);

        return "\n\n(Pastikan nominal yang Anda masukkan tidak kurang dari nol. Mari coba hitung ulang.)";
    }

    if ($hasImplausible) {
        ChatbotDiagnostics::warning(ChatbotDiagnostics::LAYER_SENTINEL_PARSER, 'rejected_implausible_value', [
            'data' => $data,
        ]);

        return "\n\n(Sepertinya ada nominal yang kurang masuk akal. Mohon sebutkan ulang angkanya, misalnya \"10 juta\" bukan \"10 miliar\".)";
    }

    if ($allEmpty || (!$hasIncomeData && !$hasWealthData)) {
        ChatbotDiagnostics::info(ChatbotDiagnostics::LAYER_SENTINEL_PARSER, 'insufficient_data_to_anchor_a_section', [
            'data' => $data,
        ]);

        return "\n\n(Bisa sebutkan nominal penghasilan atau tabungannya agar bisa saya hitung?)";
    }
}
```

### A.5.2 Penggantian sentinel menjadi hasil kalkulasi

```php
$guide = app(ChatbotZakatMalGuide::class);
$result = $guide->calculate($data, $defaults);

$summaryLines = [];
if (isset($data['income_monthly'])) {
    $summaryLines[] = sprintf(
        '- Penghasilan bulanan: Rp %s',
        number_format((int) $data['income_monthly'], 0, ',', '.')
    );
}
if (isset($data['savings'])) {
    $summaryLines[] = sprintf(
        '- Tabungan: Rp %s',
        number_format((int) $data['savings'], 0, ',', '.')
    );
}
if (isset($data['gold_gram'])) {
    $summaryLines[] = sprintf('- Emas: %d gram', (int) $data['gold_gram']);
}
if (isset($data['debt'])) {
    $summaryLines[] = sprintf(
        '- Hutang: Rp %s',
        number_format((int) $data['debt'], 0, ',', '.')
    );
}

$inputSummary = "Baik, saya coba hitungkan dari data yang Anda berikan ya:\n"
    . implode("\n", $summaryLines) . "\n"
    . "(Kalau ada yang kurang tepat, tinggal koreksi saja nominalnya.)\n\n";

$incomeLine = $result['income_is_due']
    ? sprintf(
        'Kesimpulan: wajib zakat penghasilan, sekitar Rp %s per tahun (~Rp %s per bulan).',
        number_format($result['income_zakat'], 0, ',', '.'),
        number_format((int) ($result['income_zakat'] / 12), 0, ',', '.')
    )
    : 'Kesimpulan: belum wajib zakat penghasilan saat ini.';

$wealthLine = $result['wealth_is_due']
    ? sprintf(
        'Kesimpulan: wajib zakat tabungan/emas, sekitar Rp %s per tahun.',
        number_format($result['wealth_zakat'], 0, ',', '.')
    )
    : 'Kesimpulan: belum wajib zakat tabungan/emas saat ini.';

$sections = [];
if ($hasIncomeData) {
    $sections[] = sprintf(
        "**Estimasi Zakat Penghasilan** (dari penghasilan bruto, terpisah dari tabungan):\n"
        . "- Penghasilan tahunan: Rp %s\n"
        . "- Nishab: Rp %s\n"
        . "%s",
        number_format($result['income_annual'], 0, ',', '.'),
        number_format($result['nishab'], 0, ',', '.'),
        $incomeLine
    );
}
if ($hasWealthData) {
    $sections[] = sprintf(
        "**Estimasi Zakat Tabungan & Emas** (dari harta simpanan saat ini):\n"
        . "- Aset simpanan (tabungan + emas - hutang): Rp %s\n"
        . "- Nishab: Rp %s\n"
        . "%s",
        number_format($result['wealth_base'], 0, ',', '.'),
        number_format($result['nishab'], 0, ',', '.'),
        $wealthLine
    );
}
if ($hasIncomeData && $hasWealthData) {
    $sections[] = sprintf(
        '**Total estimasi zakat: Rp %s per tahun.**',
        number_format($result['total_zakat'], 0, ',', '.')
    );
}

return "\n\n" . $inputSummary . '[[HASIL]]' . implode("\n\n", $sections) . '[[/HASIL]]';
```

## A.6 Guardrail dan Pengendalian Respons

Pengendalian respons Zakky dilakukan pada beberapa lapisan. Lampiran ini menampilkan dua bagian utama, yaitu `ChatbotGuardrailVerifier` berbasis kata kunci/regex dan `ChatbotSafetyClassifier` berbasis kemiripan embedding.

### A.6.1 ChatbotGuardrailVerifier

Lokasi file:

```text
app/Services/Chatbot/ChatbotGuardrailVerifier.php
```

```php
namespace App\Services\Chatbot;

class ChatbotGuardrailVerifier
{
    public function verify(string $llmReply, ?string $mode = null): ?string
    {
        $llmReply = preg_replace('/\[HITUNG:[^\]]*\]?/is', '', $llmReply) ?? $llmReply;

        $lowerReply = strtolower($llmReply);

        $forbiddenTopics = [
            'resep masakan',
            'cara memasak',
            'bumbu',
            'politik',
            'pemilu',
            'presiden',
            'cuaca hari ini',
            'ramalan cuaca',
            'lirik lagu',
            'chord gitar',
            'film',
            'movie',
            'bioskop',
            'sebagai asisten ai umum',
            'sebagai model bahasa',
            'as an ai language model',
            'ignore previous instructions',
            'abaikan instruksi',
            'asisten digital zakat an-nur',
            'digital assistant for zakat an-nur',
            'jangan pernah menghitung nominal zakat mal sendiri',
            'wajib hasilkan string json persis seperti ini',
        ];

        foreach ($forbiddenTopics as $topic) {
            if (str_contains($lowerReply, $topic)) {
                ChatbotDiagnostics::warning(ChatbotDiagnostics::LAYER_GUARDRAIL, 'blocked_by_keyword', [
                    'matched_keyword' => $topic,
                    'mode' => $mode,
                ]);

                return "Saya bantu untuk topik zakat dan layanan Masjid An-Nur dulu ya. Kalau mau, tanyakan soal zakat fitrah, zakat mal, fidyah, infaq/shodaqoh, atau cara bayar.";
            }
        }

        if (strlen($lowerReply) > 150) {
            $domainKeywords = [
                'zakat',
                'fitrah',
                'mal',
                'fidyah',
                'infaq',
                'shodaqoh',
                'masjid',
                'an-nur',
                'panitia',
                'amil',
                'mustahik',
                'muzakki',
                'nisab',
                'nishab',
                'haul',
                'harta',
                'penerimaan',
                'jamaah',
                'donasi',
                'rupiah',
                'beras',
                'bayar',
                'transfer',
            ];

            if ($mode === 'zakat_mal_consultation') {
                $domainKeywords = array_merge($domainKeywords, [
                    'rp',
                    'juta',
                    'penghasilan',
                    'pengeluaran',
                    'rutin',
                    'bulanan',
                    'bulan',
                    'tabungan',
                    'emas',
                    'hutang',
                    'cicilan',
                    'aset',
                    'data sementara',
                    'estimasi',
                    'perhitungan',
                    'kebutuhan hidup',
                    'bersih',
                    'wajib',
                ]);
            }

            $hasDomainKeyword = false;
            foreach ($domainKeywords as $keyword) {
                if ($this->containsWholeWord($lowerReply, $keyword)) {
                    $hasDomainKeyword = true;
                    break;
                }
            }

            if (!$hasDomainKeyword) {
                ChatbotDiagnostics::warning(
                    ChatbotDiagnostics::LAYER_GUARDRAIL,
                    'blocked_by_no_domain_keyword_heuristic',
                    [
                        'reply_length' => strlen($lowerReply),
                        'mode' => $mode,
                    ]
                );

                return "Saya bantu untuk topik zakat dan layanan Masjid An-Nur dulu ya. Kalau mau, tanyakan soal zakat fitrah, zakat mal, fidyah, infaq/shodaqoh, atau cara bayar.";
            }
        }

        return null;
    }

    private function containsWholeWord(string $haystack, string $needle): bool
    {
        return (bool) preg_match('/(?<![\pL\pN])' . preg_quote($needle, '/') . '(?![\pL\pN])/u', $haystack);
    }
}
```

### A.6.2 ChatbotSafetyClassifier

Lokasi file:

```text
app/Services/Chatbot/Safety/ChatbotSafetyClassifier.php
```

```php
namespace App\Services\Chatbot\Safety;

use App\Services\Chatbot\ChatbotDiagnostics;
use App\Services\Chatbot\Knowledge\KnowledgeEmbeddingsCache;
use App\Services\Chatbot\Providers\OpenAiEmbeddingsProvider;

class ChatbotSafetyClassifier
{
    public const CONFIDENT_THRESHOLD = 0.66;
    public const AMBIGUOUS_THRESHOLD = 0.45;

    private const K_NEIGHBORS = 5;

    private const BLOCKABLE_CATEGORIES = [
        ChatbotSafetyDataset::CATEGORY_OUT_OF_SCOPE,
        ChatbotSafetyDataset::CATEGORY_PROMPT_INJECTION,
        ChatbotSafetyDataset::CATEGORY_UNSUPPORTED_FATWA,
        ChatbotSafetyDataset::CATEGORY_PRIVACY_RISK,
        ChatbotSafetyDataset::CATEGORY_PAYMENT_VERIFICATION_RISK,
    ];

    private const REJECTION_MESSAGES = [
        ChatbotSafetyDataset::CATEGORY_OUT_OF_SCOPE =>
            'Saya bantu untuk topik zakat dan layanan Masjid An-Nur dulu ya. Kalau mau, tanyakan soal zakat fitrah, zakat mal, fidyah, infaq/shodaqoh, atau cara bayar.',
        ChatbotSafetyDataset::CATEGORY_PROMPT_INJECTION =>
            'Saya tetap Zakky, asisten zakat Masjid An-Nur, dan tidak bisa mengikuti instruksi yang mengubah peran atau membuka informasi sistem.',
        ChatbotSafetyDataset::CATEGORY_UNSUPPORTED_FATWA =>
            'Untuk keputusan hukum fikih yang bersifat pasti dan pribadi seperti ini, saya tidak bisa memberi vonis final.',
        ChatbotSafetyDataset::CATEGORY_PRIVACY_RISK =>
            'Saya tidak bisa membagikan data pribadi muzakki, mustahik, atau jamaah lain.',
        ChatbotSafetyDataset::CATEGORY_PAYMENT_VERIFICATION_RISK =>
            'Saya tidak berwenang memverifikasi, mengubah, atau membatalkan transaksi.',
    ];

    public function __construct(
        private OpenAiEmbeddingsProvider $embeddingsProvider,
        private ChatbotSafetyEmbeddingsCache $cache
    ) {
    }

    public function classify(string $text): ?array
    {
        $vector = $this->embeddingsProvider->getEmbedding($text);
        if (!$vector) {
            return null;
        }

        return $this->classifyVector($vector, $this->cache->getCachedEmbeddings());
    }

    public function classifyVector(array $vector, array $referenceEmbeddings): ?array
    {
        if (empty($referenceEmbeddings)) {
            return null;
        }

        $cases = ChatbotSafetyDataset::cases();

        $scored = [];
        foreach ($referenceEmbeddings as $index => $refVector) {
            if (!isset($cases[$index])) {
                continue;
            }

            $scored[$index] = KnowledgeEmbeddingsCache::cosineSimilarity($vector, $refVector);
        }

        if (empty($scored)) {
            return null;
        }

        arsort($scored);
        $neighbors = array_slice($scored, 0, min(self::K_NEIGHBORS, count($scored)), true);

        $voteWeight = [];
        $voteCount = [];
        foreach ($neighbors as $index => $score) {
            $category = $cases[$index]['category'];
            $voteWeight[$category] = ($voteWeight[$category] ?? 0) + $score;
            $voteCount[$category] = ($voteCount[$category] ?? 0) + 1;
        }

        arsort($voteWeight);
        $winningCategory = array_key_first($voteWeight);
        $winningScore = $voteWeight[$winningCategory] / $voteCount[$winningCategory];

        return [
            'category' => $winningCategory,
            'score' => $winningScore,
            'confidence' => self::confidenceFor($winningScore),
        ];
    }

    public static function confidenceFor(float $score): string
    {
        return match (true) {
            $score >= self::CONFIDENT_THRESHOLD => 'confident',
            $score >= self::AMBIGUOUS_THRESHOLD => 'ambiguous',
            default => 'no_match',
        };
    }

    public function checkReply(string $reply): ?string
    {
        $result = $this->classify($reply);
        if ($result === null) {
            ChatbotDiagnostics::info(ChatbotDiagnostics::LAYER_SAFETY_CLASSIFIER, 'skipped_fail_open');
            return null;
        }

        if (
            $result['confidence'] !== 'confident'
            || !in_array($result['category'], self::BLOCKABLE_CATEGORIES, true)
        ) {
            return null;
        }

        ChatbotDiagnostics::warning(ChatbotDiagnostics::LAYER_SAFETY_CLASSIFIER, 'blocked', [
            'category' => $result['category'],
            'score' => $result['score'],
        ]);

        return self::REJECTION_MESSAGES[$result['category']] ?? null;
    }
}
```

## A.7 Parser Streaming Respons

Lokasi file:

```text
app/Services/Chatbot/ChatbotStreamParser.php
```

Pada mode streaming, respons dari provider AI diterima bertahap. Parser ini menyembunyikan sentinel internal seperti `[SUGGEST:]`, menghitung sentinel `[HITUNG:]`, dan menjalankan guardrail pada batas kalimat.

```php
class ChatbotStreamParser
{
    private string $fullReply = '';
    private bool $guardrailTripped = false;

    public function __construct(
        private ChatbotSentinelParser $sentinelParser,
        private ChatbotGuardrailVerifier $guardrailVerifier,
        private ?string $mode = null
    ) {
    }

    public function parse(iterable $stream): \Generator
    {
        $buffer = '';
        $sentenceBuffer = '';
        $isSwallowing = false;
        $swallowingType = null;

        foreach ($stream as $chunk) {
            $this->fullReply .= $chunk;
            $buffer .= $chunk;

            while (strlen($buffer) > 0) {
                if ($isSwallowing) {
                    $pos = strpos($buffer, ']');
                    if ($pos !== false) {
                        $isSwallowing = false;
                        $sentinel = substr($buffer, 0, $pos + 1);
                        $buffer = substr($buffer, $pos + 1);

                        if ($swallowingType === 'hitung') {
                            $computed = trim($this->sentinelParser->parseAndCalculateSentinel($sentinel));
                            if ($computed !== '') {
                                $sentenceBuffer .= $computed;
                            }

                            $this->fullReply = str_replace($sentinel, $computed, $this->fullReply);
                        }

                        $swallowingType = null;
                    } else {
                        break;
                    }
                } else {
                    $pos = strpos($buffer, '[');
                    if ($pos !== false) {
                        $yieldStr = substr($buffer, 0, $pos);
                        if ($yieldStr !== '') {
                            $sentenceBuffer .= $yieldStr;
                            $buffer = substr($buffer, $pos);
                        }

                        $prefix9 = substr($buffer, 0, 9);
                        $prefix8 = substr($buffer, 0, 8);

                        if (strlen($buffer) < 9) {
                            if (
                                !str_starts_with("[SUGGEST:", strtoupper($prefix9))
                                && !str_starts_with("[HITUNG:", strtoupper($prefix8))
                            ) {
                                $sentenceBuffer .= '[';
                                $buffer = substr($buffer, 1);
                            } else {
                                break;
                            }
                        } else {
                            if (strtoupper($prefix9) === '[SUGGEST:') {
                                $isSwallowing = true;
                                $swallowingType = 'suggest';
                            } elseif (strtoupper($prefix8) === '[HITUNG:') {
                                $isSwallowing = true;
                                $swallowingType = 'hitung';
                            } else {
                                $sentenceBuffer .= '[';
                                $buffer = substr($buffer, 1);
                            }
                        }
                    } else {
                        $sentenceBuffer .= $buffer;
                        $buffer = '';
                    }
                }
            }

            foreach ($this->extractCompleteSentences($sentenceBuffer) as $sentence) {
                if ($this->guardrailVerifier->verify($this->fullReply, $this->mode) !== null) {
                    $this->guardrailTripped = true;
                    break;
                }

                yield $sentence;
            }

            if ($this->guardrailTripped) {
                break;
            }
        }
    }
}
```

## A.8 Ringkasan Alur Kode

Secara ringkas, alur kode AI Assistant Zakky adalah sebagai berikut:

```text
Pengguna mengirim pesan
    |
    v
ChatbotController
    |
    v
ChatbotOrchestrator
    |
    +-- Fast-path:
    |      - deteksi intent
    |      - kalkulasi fitrah/fidyah
    |      - data publik
    |
    +-- Jalur AI:
           - deteksi bahasa
           - deteksi sentimen
           - deteksi konteks percakapan
           - KnowledgeRetriever mengambil konteks
           - provider AI menghasilkan respons
           - ChatbotSentinelParser menghitung tag [HITUNG:{...}]
           - ChatbotGuardrailVerifier memeriksa respons
           - ChatbotSafetyClassifier memeriksa risiko tambahan
           - respons akhir dikirim ke pengguna
```

Lampiran ini menunjukkan bahwa sistem tidak hanya mengandalkan model AI generatif, tetapi juga menggabungkan validasi input, retrieval basis pengetahuan, kalkulasi deterministik, dan pengendalian respons berlapis.

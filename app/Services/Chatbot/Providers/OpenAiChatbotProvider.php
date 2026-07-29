<?php

namespace App\Services\Chatbot\Providers;

use App\Services\Chatbot\ChatbotDiagnostics;
use App\Services\Chatbot\ChatbotServiceInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenAiChatbotProvider implements ChatbotServiceInterface
{
    private string $apiKey;
    private string $model;
    private string $fastModel;
    private string $premiumModel;
    private string $baseUrl;
    private int $timeout;
    private bool $lastReplyWasFallback = false;
    private array $lastUsageMetadata = [];

    public function __construct(string $apiKey, string $model, string $baseUrl, int $timeout = 25, array $models = [])
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->fastModel = $models['fast'] ?? $model;
        $this->premiumModel = $models['premium'] ?? $model;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    public function sendMessage(string $message, array $context = [], string $language = 'id', array $history = []): string
    {
        $this->lastReplyWasFallback = false;
        $this->lastUsageMetadata = [];

        // Validate API key
        if (empty($this->apiKey)) {
            Log::error('OpenAI API key not configured', [
                'model' => $this->model,
            ]);
            return $this->fallback('Layanan asisten belum dikonfigurasi. Hubungi administrator.');
        }

        $systemInstruction = $this->getSystemInstruction($language, $context);
        $selectedModel = $this->selectModel($message, $context, $history);

        $url = "{$this->baseUrl}/chat/completions";

        try {
            $response = Http::withToken($this->apiKey)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->timeout($this->timeout)
                ->connectTimeout(8)
                ->retry(2, 700, function ($exception, $request) {
                    return $exception instanceof ConnectionException;
                }, throw: false)
                ->post($url, [
                    'model' => $selectedModel,
                    'messages' => $this->buildMessagesArray($systemInstruction, $history, $message),
                    'max_completion_tokens' => 500,
                ]);

            if ($response->successful()) {
                $this->lastUsageMetadata = $this->usageMetadata($selectedModel, $response->json('usage') ?? []);
                $reply = $response->json('choices.0.message.content');
                if (is_string($reply) && trim($reply) !== '') {
                    return $reply;
                }

                Log::warning('OpenAI API returned empty reply', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'model' => $selectedModel,
                ]);
            }

            Log::error('OpenAI API Error Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'model' => $selectedModel,
            ]);

            if ($response->status() === 401 || $response->status() === 403) {
                Log::error('OpenAI Authentication Failed', ['status' => $response->status()]);
                return $this->fallback('Konfigurasi belum lengkap. Coba: Total uang, Total beras, Cara bayar zakat.');
            }

            if ($response->status() === 404) {
                Log::error('OpenAI Model Not Found', ['model' => $selectedModel]);
                return $this->fallback('Asisten sedang diperbarui. Coba tanya: Total uang, Total beras, Total jiwa.');
            }

            if ($response->status() === 429) {
                Log::warning('OpenAI Rate Limit Exceeded');
                return $this->fallback('Terlalu banyak pertanyaan. Tunggu 1 menit, lalu coba lagi.');
            }

            if ($response->status() >= 500) {
                Log::error('OpenAI Server Error', ['status' => $response->status()]);
                return $this->fallback('Server sedang sibuk. Coba dalam 1 menit atau tanya: Update terakhir.');
            }

            return $this->fallback('Maaf, koneksi ke Zakky sedang belum stabil. Coba kirim ulang sebentar lagi ya.');
        } catch (Throwable $e) {
            Log::error('OpenAI API Exception', [
                'message' => $e->getMessage(),
                'model' => $selectedModel,
            ]);

            return $this->fallback('Maaf, Zakky sedang belum bisa menjawab. Coba lagi beberapa saat lagi ya.');
        }
    }

    public function streamMessage(string $message, array $context = [], string $language = 'id', array $history = []): \Generator
    {
        $this->lastReplyWasFallback = false;
        $this->lastUsageMetadata = [];

        if (empty($this->apiKey)) {
            Log::error('OpenAI API key not configured', ['model' => $this->model]);
            yield $this->fallback('Layanan asisten belum dikonfigurasi. Hubungi administrator.');
            return;
        }

        $systemInstruction = $this->getSystemInstruction($language, $context);
        $selectedModel = $this->selectModel($message, $context, $history);
        $url = "{$this->baseUrl}/chat/completions";

        try {
            $response = Http::withToken($this->apiKey)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'text/event-stream',
                ])
                ->timeout($this->timeout)
                ->connectTimeout(8)
                ->withOptions(['stream' => true])
                ->post($url, [
                    'model' => $selectedModel,
                    'messages' => $this->buildMessagesArray($systemInstruction, $history, $message),
                    'max_completion_tokens' => 500,
                    'stream' => true,
                    'stream_options' => ['include_usage' => true],
                ]);

            if ($response->successful()) {
                $stream = $response->toPsrResponse()->getBody();
                $buffer = '';

                while (!$stream->eof()) {
                    $buffer .= $stream->read(1024);
                    while (($pos = strpos($buffer, "\n")) !== false) {
                        $line = trim(substr($buffer, 0, $pos));
                        $buffer = substr($buffer, $pos + 1);

                        if (str_starts_with($line, 'data: ')) {
                            $data = trim(substr($line, 6));
                            if ($data === '[DONE]') {
                                break 2;
                            }

                            $json = json_decode($data, true);
                            if (isset($json['usage']) && is_array($json['usage'])) {
                                $this->lastUsageMetadata = $this->usageMetadata($selectedModel, $json['usage']);
                            }

                            if (isset($json['choices'][0]['delta']['content'])) {
                                yield $json['choices'][0]['delta']['content'];
                            }
                        }
                    }
                }
                return;
            }

            Log::error('OpenAI API Stream Error Response', [
                'status' => $response->status(),
                'model' => $selectedModel,
            ]);

            if ($response->status() === 429) {
                yield $this->fallback('Terlalu banyak pertanyaan. Tunggu 1 menit, lalu coba lagi.');
            } else {
                yield $this->fallback('Maaf, koneksi ke Zakky sedang belum stabil. Coba kirim ulang sebentar lagi ya.');
            }
        } catch (Throwable $e) {
            Log::error('OpenAI API Stream Exception', [
                'message' => $e->getMessage(),
                'model' => $selectedModel,
            ]);

            yield $this->fallback('Maaf, Zakky sedang belum bisa menjawab. Coba lagi beberapa saat lagi ya.');
        }
    }

    public function wasLastReplyFallback(): bool
    {
        return $this->lastReplyWasFallback;
    }

    public function lastUsageMetadata(): array
    {
        return $this->lastUsageMetadata;
    }

    private function fallback(string $message): string
    {
        $this->lastReplyWasFallback = true;
        return ChatbotServiceInterface::FALLBACK_PREFIX . $message;
    }

    private function usageMetadata(string $model, array $usage): array
    {
        $promptTokens = (int) ($usage['prompt_tokens'] ?? 0);
        $completionTokens = (int) ($usage['completion_tokens'] ?? 0);
        $totalTokens = (int) ($usage['total_tokens'] ?? ($promptTokens + $completionTokens));
        // OpenAI applies automatic prompt caching for prompts over ~1024 tokens - our system
        // prompt alone is already past that (see Bab 15) - and reports how much of it hit cache
        // here. Surfacing this turns "caching is probably working" into a measured number instead
        // of an assumption, and lets estimateCostUsd() below bill cached tokens at their real
        // (discounted) rate instead of overstating spend once caching kicks in.
        $cachedTokens = (int) ($usage['prompt_tokens_details']['cached_tokens'] ?? 0);

        return [
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'cached_tokens' => $cachedTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'estimated_cost_usd' => $this->estimateCostUsd($model, $promptTokens, $cachedTokens, $completionTokens),
        ];
    }

    private function estimateCostUsd(string $model, int $promptTokens, int $cachedTokens, int $completionTokens): float
    {
        $pricingPerMillion = [
            'gpt-5.6-luna' => ['input' => 1.00, 'cached_input' => 0.50, 'output' => 6.00],
            'gpt-5.6-terra' => ['input' => 2.50, 'cached_input' => 1.25, 'output' => 15.00],
            'gpt-5.6-sol' => ['input' => 5.00, 'cached_input' => 2.50, 'output' => 30.00],
            'gpt-5.6' => ['input' => 5.00, 'cached_input' => 2.50, 'output' => 30.00],
        ];

        $pricing = $pricingPerMillion[$model] ?? null;
        if ($pricing === null) {
            return 0.0;
        }

        // Cached tokens are billed at OpenAI's discounted cached-input rate (~50% of fresh input),
        // not the full input rate - counting them as full-price would overstate real spend on
        // every turn where the (mostly-stable) system prompt prefix hits cache.
        $freshPromptTokens = max(0, $promptTokens - $cachedTokens);

        return round(
            ($freshPromptTokens / 1_000_000 * $pricing['input'])
            + ($cachedTokens / 1_000_000 * $pricing['cached_input'])
            + ($completionTokens / 1_000_000 * $pricing['output']),
            8
        );
    }

    private function selectModel(string $message, array $context, array $history = []): string
    {
        $normalizedMessage = mb_strtolower($message);

        if ($this->needsPremiumModel($normalizedMessage, $context)) {
            $model = $this->premiumModel;
            $reason = 'premium_signal';
        } elseif ($this->canUseFastModel($normalizedMessage, $context)) {
            $model = $this->fastModel;
            $reason = 'fast_signal';
        } else {
            $model = $this->model;
            $reason = 'default_tier';
        }

        ChatbotDiagnostics::info(ChatbotDiagnostics::LAYER_LLM_PROVIDER, 'model_routed', [
            'model_used' => $model,
            'route_reason' => $reason,
            'message_length' => mb_strlen($message),
            'conversation_turn_count' => count($history),
        ]);

        return $model;
    }

    private function needsPremiumModel(string $message, array $context): bool
    {
        if (count($context) >= 3 || mb_strlen($message) > 350) {
            return true;
        }

        // No overlapping substrings allowed here (e.g. NOT both 'hitung' and 'perhitungan', NOT
        // both 'utang' and 'hutang') - one keyword being a substring of another silently double-
        // counts a single real signal as two, defeating the >=2-signal check below entirely.
        $premiumKeywords = [
            'hitung', 'zakat mal', 'zakat maal', 'nishab', 'nisab',
            'haul', 'emas', 'tabungan', 'hutang', 'aset', 'penghasilan',
            'gaji', 'investasi', 'saham', 'usaha', 'warisan', 'konsultasi',
        ];

        $matches = 0;
        foreach ($premiumKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                $matches++;
            }
        }

        // A single passing mention of one keyword ("gaji saya turun, ada saran?") isn't a real
        // calculation/consultation signal - it was silently costing the premium model's ~2s extra
        // latency over the default tier for no reasoning benefit. Two or more distinct keywords,
        // or one keyword paired with an actual figure ("emas 100 gram"), are real signals.
        return $matches >= 2 || ($matches === 1 && preg_match('/\d/', $message) === 1);
    }

    private function canUseFastModel(string $message, array $context): bool
    {
        if (!empty($context) || mb_strlen($message) > 120) {
            return false;
        }

        $fastPatterns = [
            'halo', 'hai', 'assalamualaikum', 'terima kasih', 'makasih',
            'apa itu zakat', 'jadwal', 'lokasi', 'alamat', 'kontak',
            'cara bayar', 'total uang', 'total beras', 'total jiwa',
        ];

        foreach ($fastPatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }

        return str_word_count($message) <= 6;
    }

    private function getSystemInstruction(string $language, array $context): string
    {
        // Structured into labeled sections (HARD RULES vs STYLE) rather than one flat run-on
        // paragraph - correctness-critical rules (never self-calculate, emit exact JSON schema,
        // confirm intent first) need to stand out from soft tone/style preferences, not compete
        // for attention buried among ~35 other sentences. Both languages carry the full hard-rule
        // set - the 'id' block used to be the only one with these rules at all (bruto clarification,
        // advanced-topic restriction, even the [HITUNG:] sentinel itself weren't in the 'en' block),
        // meaning an English-speaking user's conversation had zero protection against the LLM
        // hallucinating zakat mal figures - the exact failure mode the sentinel pattern (see
        // ChatbotSentinelParser) exists to prevent.
        $systemInstruction = "You are Zakky, the digital assistant for Zakat An-Nur. Be helpful, warm, and concise — like a knowledgeable mosque committee member.\n\n"
            . "HARD RULES (never violate these):\n"
            . "- Do not assume the user wants a zakat mal calculation just because their message mentions a salary/savings/gold/debt figure — it could be context for a different question. Confirm their intent first (e.g. 'Would you like me to help estimate your zakat mal?') before collecting any data.\n"
            . "- If information is missing, do NOT guess the number — ASK for it. If a number is ambiguous, a range, unusually large, missing a time period, or uses an unclear unit, confirm it first; never guess. If the user corrects a number, replace the old value with the new one.\n"
            . "- NEVER calculate a zakat mal amount yourself — this applies to ALL zakat mal topics, not just income/savings/gold.\n"
            . "- Once the user confirms or explicitly asks to be calculated, collect asset info (gross/pre-tax monthly salary, savings, gold, debt). If the user describes their salary as 'net salary', 'take-home pay', or 'after tax/insurance deductions', do NOT use that figure directly — clarify that Masjid An-Nur calculates zakat penghasilan from the gross salary (following BAZNAS), then ask for the gross figure. If the user does NOT say whether it's net or gross (just states a plain salary figure), ASSUME it's already gross and proceed — do NOT ask a bruto/net clarifying question that was never triggered by anything the user said, especially not after they already confirmed the data is correct and asked you to calculate.\n"
            . "- For advanced zakat mal topics (agriculture/plantation, livestock, stocks/investment/mutual funds, rental property, inheritance, complex business with stock/receivables): the [HITUNG:] sentinel does NOT cover these. Do NOT apply a formula/percentage to the user's own real figures for these topics (e.g. don't calculate 'your 2000 kg harvest means your zakat is 200 kg') — only explain the formula and nisab in general terms (illustrative example numbers are fine), then refer the user to the committee/ustadz for a final figure.\n"
            . "- If the user says mid-consultation that they already paid/transferred, do NOT continue calculating or asking for more data — direct them straight to payment confirmation with the committee.\n"
            . "- If the user follows up AFTER a result was already shown by changing just one variable (e.g. 'what if my savings were 100 million instead?'), immediately recalculate with that one variable updated and output the [HITUNG:] JSON again — do NOT ask the user to restate the other, unchanged variables, and do NOT ask a confirmation question first when the follow-up itself already states the new figure clearly.\n"
            . "- If the user never mentions the haul (whether the asset has been held for a full year) at all, do NOT treat it as required additional data and do NOT stop to ask about it — ASSUME the haul condition is met for an initial estimate, calculate normally, and add a brief note that the result assumes the haul condition is met and can be confirmed with the committee/ustadz if unsure. Once the user has confirmed their data is correct and asked you to calculate, do NOT block the calculation on haul status that was never raised by anything the user said.\n"
            . "- If enough variables are known for zakat penghasilan/tabungan/emas, you MUST output this exact JSON string (embedded in your message): [HITUNG:{\"income_monthly\":10000000,\"savings\":50000000,\"gold_gram\":0,\"debt\":0}] All keys are optional, values are integers in rupiah or grams of gold.\n"
            . "- Do not output [SUGGEST] tags, quick replies, buttons, or UI actions.\n"
            . "- Only answer from the 'Official Context' below. If the answer isn't there, do not stop at a bare refusal. Give a short general orientation if it is safe, name the missing detail, and tell the user what to prepare before contacting the committee (for example: name, payment type, date, amount, proof of transfer, or a short case chronology). Use wording like: 'That detail isn't in my Masjid An-Nur guide yet. In general, this may depend on the committee's current policy. Please prepare ... and confirm it with the committee.'\n\n"
            . "STYLE:\n"
            . "- Use plain, everyday language. If you use a fiqh term (like Haul/Nishab), always add a short explanation in parentheses.\n"
            . "- For FAQs, answer in 2-4 sentences. For consultations, guide step by step and ask for one key missing piece of data at a time.\n"
            . "- Before calculating, briefly summarize the data the user has given so a wrong number is easy to correct.\n"
            . "- After a calculation result, always include one practical next step: prepare the payment based on the estimate, confirm the official payment method with the committee, or bring the calculation summary if the user wants manual verification. Do not end only with a disclaimer or an empty question.\n"
            . "- When more detail is needed, ask one focused clarification question in plain text. If helpful, include 2-4 numbered options plus 'Other' so the user can answer freely.\n"
            . "- For location or payment questions (only when asked): 'Visit Masjid An-Nur during the last 10 days of Ramadan. Location: https://maps.app.goo.gl/o4SULwNTn9QYkQba9'\n"
            . "- Decline off-topic questions politely and redirect to zakat.\n"
            . "- Always reply in the same language as the user.";

        if ($language === 'id') {
            $systemInstruction = "Kamu adalah Zakky, asisten digital Zakat An-Nur. Bicara seperti panitia masjid yang tahu betul soal zakat — hangat, langsung ke intinya, tidak perlu berlebihan.\n\n"
                . "ATURAN KERAS (jangan pernah dilanggar):\n"
                . "- Jangan langsung menganggap user mau dihitungkan zakat mal hanya karena pesannya menyebut angka gaji/tabungan/emas/hutang — itu bisa jadi konteks untuk pertanyaan lain. Konfirmasi dulu niatnya (mis. 'Mau saya bantu hitungkan estimasi zakat mal-nya?') sebelum mulai mengumpulkan data.\n"
                . "- Jika informasi kurang, JANGAN menebak angka, BERTANYALAH untuk melengkapi data. Jika angka ambigu, berupa rentang, terlalu besar, tanpa periode waktu, atau memakai satuan tidak jelas, konfirmasi dulu; jangan menebak. Jika user mengoreksi angka, ganti nilai lama dengan nilai baru.\n"
                . "- JANGAN PERNAH menghitung nominal zakat mal sendiri — aturan ini berlaku untuk SEMUA topik zakat mal, bukan cuma penghasilan/tabungan/emas.\n"
                . "- Setelah user mengonfirmasi atau memang secara eksplisit minta dihitungkan, baru kumpulkan informasi aset (gaji bulanan kotor/bruto sebelum potongan, tabungan, emas, hutang). Jika user menyebut angka gajinya sebagai 'gaji bersih', 'take home pay', atau 'setelah potongan pajak/BPJS', JANGAN langsung pakai angka itu — klarifikasi dulu bahwa Masjid An-Nur menghitung zakat penghasilan dari gaji kotor/bruto (mengikuti BAZNAS), lalu tanyakan angka bruto-nya. Kalau user TIDAK menyebutkan itu bersih atau kotor sama sekali (cuma sebut angka gaji polos), ANGGAP itu sudah angka bruto dan LANJUTKAN — JANGAN tanya klarifikasi bruto/bersih yang tidak pernah dipicu apa pun dari user, apalagi setelah user sudah mengonfirmasi datanya benar dan minta dihitungkan.\n"
                . "- Untuk topik zakat mal lanjutan (pertanian/perkebunan, peternakan, saham/investasi/reksadana, properti sewa, warisan, usaha dengan stok/piutang kompleks): sentinel [HITUNG:] TIDAK mencakup topik ini. JANGAN menerapkan rumus/persentase ke angka pribadi milik user untuk topik-topik itu (mis. jangan hitung 'panen Anda 2000 kg jadi zakatnya 200 kg') — jelaskan rumus dan nisabnya secara umum saja (boleh pakai contoh ilustrasi seperti di panduan), lalu arahkan ke panitia/ustadz untuk angka final, sesuai keterbatasan di panduan 'Batas Perhitungan Otomatis Zakat Mal Lanjutan'.\n"
                . "- Jika di tengah konsultasi user bilang sudah bayar/transfer duluan, JANGAN lanjut menghitung atau meminta data lagi — arahkan langsung ke konfirmasi pembayaran ke panitia.\n"
                . "- Jika user follow-up SETELAH hasil sudah keluar dengan mengubah satu variabel saja (mis. 'kalau tabungan saya jadi 100 juta gimana?'), LANGSUNG hitung ulang dengan variabel itu diperbarui dan keluarkan lagi JSON [HITUNG:] — JANGAN minta user mengulang data lain yang tidak berubah, dan JANGAN tanya konfirmasi dulu kalau follow-up-nya sendiri sudah menyebutkan angka baru dengan jelas.\n"
                . "- Kalau user SAMA SEKALI tidak menyebut status haul (harta sudah dimiliki/disimpan genap setahun atau belum), JANGAN anggap itu data wajib tambahan dan JANGAN berhenti untuk menanyakannya — ANGGAP syarat haul terpenuhi untuk estimasi awal, tetap hitung seperti biasa, lalu sertakan catatan singkat bahwa hasil ini mengasumsikan syarat haul terpenuhi dan bisa dikonfirmasi ke panitia/ustadz kalau ragu. Kalau user sudah mengonfirmasi datanya benar dan minta dihitungkan, JANGAN tunda perhitungan hanya karena status haul yang tidak pernah disinggung user.\n"
                . "- Jika variabel cukup untuk zakat penghasilan/tabungan/emas, WAJIB hasilkan string JSON persis seperti ini (selipkan di pesanmu): [HITUNG:{\"income_monthly\":10000000,\"savings\":50000000,\"gold_gram\":0,\"debt\":0}] Semua kunci opsional, nilai dalam integer rupiah atau gram emas.\n"
                . "- JANGAN membuat tag [SUGGEST], quick reply, tombol, atau instruksi UI.\n"
                . "- Jawab hanya dari 'Konteks resmi' di bawah. Kalau informasinya tidak ada, jangan berhenti di penolakan pendek. Beri konteks umum yang aman kalau memungkinkan, sebutkan detail yang belum ada di panduan, lalu beri langkah konkret yang perlu disiapkan sebelum menghubungi panitia (mis. nama, jenis pembayaran, tanggal, nominal, bukti transfer, atau kronologi singkat kasus). Gunakan pola seperti: 'Saya belum punya detail itu di panduan Masjid An-Nur. Secara umum, hal ini bisa bergantung pada kebijakan panitia/periode berjalan. Siapkan ... lalu konfirmasi ke panitia ya.'\n\n"
                . "GAYA BICARA:\n"
                . "- Gunakan istilah awam. Jika menggunakan istilah fiqih (seperti Haul/Nishab), selalu berikan penjelasan singkat di dalam kurung.\n"
                . "- Untuk FAQ, jawab 2-4 kalimat. Untuk konsultasi, pandu bertahap dan tanyakan satu data terpenting yang belum ada.\n"
                . "- Sebelum menghitung, rangkum singkat data yang sudah user berikan agar kesalahan angka mudah dikoreksi. Gunakan pembuka rangkuman yang natural seperti 'Baik, saya rangkum dulu ya:' atau 'Sejauh ini saya catat:'; hindari frasa kaku seperti 'Data sementara:', 'berdasarkan konteks resmi', 'saya diprogram', atau 'di luar jangkauan'.\n"
                . "- Agar terasa seperti teman konsultasi, akui jawaban pendek user, jelaskan singkat kenapa data tertentu ditanya, jangan mengulang semua data di setiap giliran, jangan bertanya beruntun, dan beri opsi praktis hanya saat membantu.\n"
                . "- Jika user tampak bingung, takut salah, malu, atau tidak tahu angka pasti, tenangkan secara natural dan tawarkan langkah paling ringan atau asumsi sementara yang mudah dikoreksi.\n"
                . "- Bedakan edukasi dan konsultasi: jika user hanya ingin belajar konsep, jawab konsepnya; jika user minta dihitung, baru kumpulkan data dan hitung. Jika user mengubah topik atau bilang 'nanti dulu', jawab topik barunya dan tawarkan lanjut konsultasi setelahnya.\n"
                . "- Sebelum hasil final, rangkum data penting dan beri sinyal bahwa user bisa koreksi. Setelah hasil keluar, ubah angka menjadi langkah praktis: siapkan pembayaran sesuai estimasi, konfirmasi metode pembayaran resmi ke panitia, atau bawa ringkasan hitungan jika ingin diverifikasi manual. Sebutkan asumsi jika ada, dan tutup dengan opsi lanjut yang jelas, bukan disclaimer kosong atau pertanyaan kosong seperti 'Ada lagi?'.\n"
                . "- Untuk case khusus, gunakan alur triase: identifikasi jenis harta, klasifikasikan ke kategori zakat, cek syarat utama, beri estimasi awal jika aman, sebutkan faktor yang bisa mengubah hasil, lalu beri langkah berikutnya.\n"
                . "- Hindari terlalu sering memakai kalimat defensif seperti 'Zakky tidak menetapkan keputusan final'; gunakan redaksi lebih natural bahwa Zakky memberi arah awal dan detail kasus dapat dikonfirmasi ke panitia atau ustadz.\n"
                . "- Kalau butuh klarifikasi, ajukan pertanyaan dalam teks biasa. Bila cocok, beri 2-4 opsi bernomor dan opsi 'Lainnya' agar user bisa menjawab kondisi yang berbeda.\n"
                . "- Kalau ditanya soal lokasi atau cara bayar, sampaikan: 'Silakan datang ke Masjid An-Nur pada 10 hari terakhir Ramadhan. Lokasi: https://maps.app.goo.gl/o4SULwNTn9QYkQba9' — tapi hanya kalau ditanya.\n"
                . "- Kalau pertanyaan di luar zakat/Islam/masjid, tolak dengan singkat dan kembalikan ke topik zakat.\n"
                . "- Balas dalam bahasa yang sama dengan pertanyaan.";
        }

        if (!empty($context)) {
            // Hint-only entries (no title/answer) carry sentiment/correction hints when no
            // knowledge context matched — they shouldn't render as an empty "- Konteks: " bullet.
            $knowledgeItems = collect($context)->filter(fn ($item) => isset($item['title']));
            if ($knowledgeItems->isNotEmpty()) {
                $contextText = $knowledgeItems
                    ->map(fn ($item) => '- ' . ($item['title'] ?? 'Konteks') . ': ' . ($item['answer'] ?? ''))
                    ->implode("\n");
                $systemInstruction .= ($language === 'id' ? "\n\nKonteks resmi:\n" : "\n\nOfficial Context:\n") . $contextText;
            }

            $sentimentHint = $context[0]['_sentiment_hint'] ?? null;
            if ($sentimentHint) {
                $systemInstruction .= "\n\n" . $sentimentHint;
            }

            $correctionHint = $context[0]['_correction_hint'] ?? null;
            if ($correctionHint) {
                $systemInstruction .= "\n\n" . $correctionHint;
            }

            $conversationHint = $context[0]['_conversation_hint'] ?? null;
            if ($conversationHint) {
                $systemInstruction .= "\n\n" . $conversationHint;
            }
        }

        return $systemInstruction;
    }

    private function buildMessagesArray(string $systemInstruction, array $history, string $currentMessage): array
    {
        $messages = [
            ['role' => 'system', 'content' => $systemInstruction],
        ];

        // Sliding Window Memory: keep the last 8 interactions for multi-turn consultation.
        $recentHistory = array_slice($history, -8);

        foreach ($recentHistory as $hist) {
            if (!empty($hist['question'])) {
                $messages[] = ['role' => 'user', 'content' => $hist['question']];
            }
            if (!empty($hist['answer'])) {
                $messages[] = ['role' => 'assistant', 'content' => $hist['answer']];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $currentMessage];

        return $messages;
    }
}

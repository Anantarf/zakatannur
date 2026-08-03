<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AiChatLog;
use App\Services\Chatbot\ChatbotOrchestrator;
use Illuminate\Support\Str;

$orchestrator = app(ChatbotOrchestrator::class);
$startedAt = date('c');

$scenarios = [
    [
        'code' => 'PERF-01',
        'question' => 'Hitungkan zakat fitrah untuk 4 orang.',
        'path' => 'Berbasis aturan',
        'context' => [],
    ],
    [
        'code' => 'PERF-02',
        'question' => 'Berapa total penerimaan zakat tahun ini?',
        'path' => 'Data publik',
        'context' => [],
    ],
    [
        'code' => 'PERF-03',
        'question' => 'Apa yang dimaksud nisab dan haul dalam zakat mal?',
        'path' => 'Pengetahuan cepat / retrieval langsung',
        'context' => [],
    ],
    [
        'code' => 'PERF-04',
        'question' => 'Saya punya penghasilan Rp10.000.000 per bulan, tabungan Rp100.000.000, emas 0 gram, dan hutang 0. Hitungkan zakat mal saya.',
        'path' => 'RAG dengan kalkulasi deterministik',
        'context' => [],
    ],
];

$rows = [];

foreach ($scenarios as $scenario) {
    for ($iteration = 1; $iteration <= 5; $iteration++) {
        $sessionId = 'babiv-perf-' . strtolower($scenario['code']) . '-' . $iteration . '-' . Str::uuid()->toString();
        $started = microtime(true);
        $response = $orchestrator->handle($scenario['question'], $scenario['context'], $sessionId);
        $durationMs = (int) round((microtime(true) - $started) * 1000);
        $log = AiChatLog::where('session_id', $sessionId)->latest('id')->first();

        $rows[] = [
            'code' => $scenario['code'],
            'iteration' => $iteration,
            'question' => $scenario['question'],
            'path' => $scenario['path'],
            'source' => $response->source,
            'model' => $log?->model ?? '-',
            'duration_ms' => $durationMs,
            'total_tokens' => $log?->total_tokens ?? 0,
            'status' => $response->statusCode < 400 ? 'berhasil' : 'gagal',
        ];
    }
}

$summary = [];

foreach ($scenarios as $scenario) {
    $items = array_values(array_filter($rows, fn (array $row): bool => $row['code'] === $scenario['code']));
    $durations = array_column($items, 'duration_ms');
    $tokens = array_column($items, 'total_tokens');

    $summary[] = [
        'code' => $scenario['code'],
        'path' => $scenario['path'],
        'duration_ms_min' => min($durations),
        'duration_ms_max' => max($durations),
        'duration_ms_avg' => round(array_sum($durations) / count($durations), 2),
        'tokens_min' => min($tokens),
        'tokens_max' => max($tokens),
        'tokens_avg' => round(array_sum($tokens) / count($tokens), 2),
        'success_count' => count(array_filter($items, fn (array $row): bool => $row['status'] === 'berhasil')),
        'total_count' => count($items),
    ];
}

$output = [
    'measured_at' => $startedAt,
    'rows' => $rows,
    'summary' => $summary,
];

file_put_contents(__DIR__ . '/pengukuran-performa-berulang.json', json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

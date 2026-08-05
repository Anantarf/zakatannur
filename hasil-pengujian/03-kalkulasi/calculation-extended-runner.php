<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Chatbot\ChatbotOrchestrator;
use App\Services\Chatbot\ChatbotSentinelParser;
use Illuminate\Support\Str;

$orchestrator = app(ChatbotOrchestrator::class);
$sentinelParser = app(ChatbotSentinelParser::class);

$cases = [
    'CALC-01' => 'Hitungkan zakat fitrah untuk 4 orang.',
    'CALC-02' => 'Hitungkan fidyah untuk 3 hari.',
    'CALC-03' => 'Saya punya penghasilan Rp10.000.000 per bulan, tabungan Rp100.000.000, emas 0 gram, dan hutang 0. Hitungkan zakat mal saya.',
    'CALC-04' => 'Saya punya penghasilan Rp4.000.000 per bulan dan tabungan Rp2.000.000. Hitungkan zakat mal saya.',
    'CALC-05' => 'Saya punya penghasilan Rp7.640.144 per bulan, tabungan Rp0, emas 0 gram, hutang 0. Hitungkan zakat mal saya.',
    'CALC-06' => 'Fitrah tahun 2026 itu berapa ya per orang?',
    'CALC-07' => 'Saya panen padi 2000 kg. Hitungkan zakat pertanian saya sekarang.',
];

foreach ($cases as $code => $message) {
    $response = $orchestrator->handle(
        $message,
        [],
        'babiv-calc-extended-' . strtolower($code) . '-' . Str::uuid()->toString()
    );

    echo "## {$code}\n";
    echo "Pertanyaan: {$message}\n";
    echo "Source: {$response->source}\n";
    echo "Status: {$response->statusCode}\n";
    echo "Respons aktual:\n{$response->reply}\n\n";
}

$deterministicCases = [
    'CALC-08' => [
        'description' => 'Zakat penghasilan saja hanya menampilkan komponen penghasilan.',
        'sentinel' => '[HITUNG:{"income_monthly":10000000}]',
    ],
    'CALC-09' => [
        'description' => 'Zakat tabungan saja hanya menampilkan komponen tabungan/emas.',
        'sentinel' => '[HITUNG:{"savings":100000000}]',
    ],
    'CALC-10' => [
        'description' => 'Field tabungan/emas bernilai nol tidak memunculkan komponen tabungan/emas.',
        'sentinel' => '[HITUNG:{"income_monthly":10000000,"savings":0,"gold_gram":0,"debt":0}]',
    ],
];

foreach ($deterministicCases as $code => $case) {
    $reply = $sentinelParser->parseAndCalculateSentinel($case['sentinel']);

    echo "## {$code}\n";
    echo "Pertanyaan: {$case['description']}\n";
    echo "Source: deterministic_sentinel_parser\n";
    echo "Status: 200\n";
    echo "Sentinel: {$case['sentinel']}\n";
    echo "Respons aktual:\n{$reply}\n\n";
}

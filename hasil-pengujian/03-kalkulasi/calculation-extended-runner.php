<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Chatbot\ChatbotOrchestrator;
use Illuminate\Support\Str;

$orchestrator = app(ChatbotOrchestrator::class);

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

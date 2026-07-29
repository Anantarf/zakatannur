<?php

namespace Tests\Unit;

use App\Services\Chatbot\Knowledge\ChatbotBehaviorDataset;
use Tests\TestCase;

class ChatbotBehaviorDatasetTest extends TestCase
{
    private function expectClosureFor(string $scenarioName): \Closure
    {
        $case = collect(ChatbotBehaviorDataset::cases())->firstWhere('name', $scenarioName);
        $this->assertNotNull($case, "Scenario '{$scenarioName}' not found in ChatbotBehaviorDataset");

        return $case['expect'];
    }

    /**
     * @dataProvider correctionRepliesProvider
     */
    public function test_correction_scenario_accepts_real_model_phrasings(string $reply): void
    {
        // These are real replies from `chatbot:eval-behavior` runs (2026-07-29) - the expect()
        // closure was tightened twice against false negatives on genuinely correct behavior
        // phrased in ways the closure hadn't anticipated (no "ganti/koreksi/catat/ubah" keyword,
        // or the old value restated only to negate it). Each of these previously failed for a
        // different reason before the fix - pinned here so a future edit to the closure can't
        // silently regress on any of them without running the real eval command.
        $expect = $this->expectClosureFor('mengganti angka lama saat user mengoreksi');

        $this->assertTrue($expect($reply));
    }

    public static function correctionRepliesProvider(): array
    {
        return [
            'explicit ganti + negated old value' => ['Baik, saya ganti ya: gaji Rp7,5 juta per bulan, bukan Rp75 juta, dan tabungan Rp10 juta.'],
            'no acknowledgment keyword, full-digit new value' => ['Baik, gaji yang benar Rp7.500.000 per bulan, dan tabungan Rp10.000.000. Apakah ada emas atau hutang yang perlu dicatat?'],
        ];
    }

    public function test_correction_scenario_rejects_a_reply_that_keeps_the_old_value(): void
    {
        $expect = $this->expectClosureFor('mengganti angka lama saat user mengoreksi');

        $this->assertFalse($expect('Baik, saya catat gaji Rp75 juta per bulan dan tabungan Rp10 juta.'));
    }
}

<?php

namespace Tests\Unit;

use App\Services\Chatbot\ChatbotActionDetector;
use Tests\TestCase;

class ChatbotActionDetectorTest extends TestCase
{
    private function detector(): ChatbotActionDetector
    {
        return new ChatbotActionDetector();
    }

    /**
     * @dataProvider hijackedCalculationRequestProvider
     */
    public function test_personal_calculation_requests_are_not_hijacked_into_public_data_intents(string $message): void
    {
        // "zakat" + "berapa" is an extremely natural way to phrase a personal zakat mal
        // calculation question, but it also matches the wording used for the mosque's aggregate
        // "total zakat terkumpul" public-data intents. Before this fix, messages like these never
        // reached AI at all - they were quick-answered with an unrelated mosque-wide total instead
        // of starting a zakat mal consultation.
        $this->assertNull($this->detector()->intent($message));
    }

    public static function hijackedCalculationRequestProvider(): array
    {
        return [
            ['Saya mau hitung zakat mal, gaji 10 juta, berapa zakatnya?'],
            ['Zakat penghasilan saya berapa kalau gaji 8 juta?'],
            ['Saya panen 2000 kg gabah pengairan alami, hitungkan zakatnya berapa kg.'],
            ['Tabungan saya 50 juta, hutang 10 juta, berapa zakatnya?'],
        ];
    }

    public function test_genuine_aggregate_total_questions_still_resolve_to_public_data_intents(): void
    {
        // The fix must not swallow the legitimate "mosque-wide total" questions these intents
        // exist for - only personal-figure calculation requests should be excluded. Both of these
        // resolve to ask_total_summary (it's checked before ask_total_rice and "terkumpul"
        // qualifies), which is pre-existing behavior unaffected by this fix - the public-data
        // responder still surfaces rice figures in its summary reply either way.
        $this->assertSame('ask_total_summary', $this->detector()->intent('Berapa total penerimaan zakat saat ini?'));
        $this->assertSame('ask_total_summary', $this->detector()->intent('Beras terkumpul berapa kg?'));
    }
}

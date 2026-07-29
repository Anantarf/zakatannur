<?php

namespace Tests\Unit;

use App\Services\Chatbot\ChatbotSentimentDetector;
use Tests\TestCase;

class ChatbotSentimentDetectorTest extends TestCase
{
    private ChatbotSentimentDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new ChatbotSentimentDetector();
    }

    /**
     * @dataProvider neutralQuestionsProvider
     */
    public function test_everyday_question_words_do_not_trigger_frustrated_sentiment(string $message): void
    {
        $this->assertSame('neutral', $this->detector->detect($message));
    }

    public static function neutralQuestionsProvider(): array
    {
        return [
            ['Mana yang lebih murah, zakat pertanian pengairan alami atau berbayar?'],
            ['Kok zakat fitrah beda-beda nominalnya di tiap daerah?'],
            ['Saya salah pilih kategori pembayaran waktu itu'],
            ['Kenapa nisab emas dan perak beda gram-nya?'],
        ];
    }

    public function test_genuine_frustration_still_detected(): void
    {
        $this->assertSame('frustrated', $this->detector->detect('Aduh error terus, ngasal banget sistemnya'));
    }

    /**
     * @dataProvider notACorrectionMessageProvider
     */
    public function test_common_words_containing_correction_substrings_do_not_false_positive(string $message): void
    {
        // isCorrectingPreviousNumber() used to str_contains() match 'eh' as a bare substring,
        // which is also inside "boleh"/"oleh" - both extremely common Indonesian words - and
        // 'salah' without excluding the equally common phrase "salah satu" ("one of"). Any message
        // with one of those words plus any digit anywhere used to false-positive into a
        // "_correction_hint" injected into the LLM prompt for an ordinary first-time question.
        $this->assertFalse($this->detector->isCorrectingPreviousNumber($message));
    }

    public static function notACorrectionMessageProvider(): array
    {
        return [
            ['Apakah boleh saya bayar zakat fitrah untuk 4 orang sekaligus?'],
            ['Salah satu syarat zakat adalah harta sudah mencapai nisab 85 gram'],
            ['Ini bukan zakat mal, tapi zakat fitrah untuk 4 jiwa'],
        ];
    }

    /**
     * @dataProvider genuineCorrectionMessageProvider
     */
    public function test_genuine_number_corrections_are_still_detected(string $message): void
    {
        $this->assertTrue($this->detector->isCorrectingPreviousNumber($message));
    }

    public static function genuineCorrectionMessageProvider(): array
    {
        return [
            ['eh salah, harusnya 12 juta bukan 10 juta'],
            ['Maksudnya 5 juta, bukan 50 juta'],
            ['Ralat, seharusnya 8 juta'],
        ];
    }
}

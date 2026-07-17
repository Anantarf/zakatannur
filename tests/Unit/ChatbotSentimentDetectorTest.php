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
}

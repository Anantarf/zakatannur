<?php

namespace Tests\Unit;

use App\Services\Chatbot\ChatbotConversationContext;
use App\Services\Chatbot\ChatbotSentimentDetector;
use Tests\TestCase;

class ChatbotConversationContextTest extends TestCase
{
    private function context(): ChatbotConversationContext
    {
        return new ChatbotConversationContext(new ChatbotSentimentDetector());
    }

    /**
     * @dataProvider unrelatedDigitMessageProvider
     */
    public function test_bare_digits_do_not_trigger_zakat_mal_consultation_mode(string $message): void
    {
        // Bab 10.18: a bare "any digit" check used to sit in $hasFinancialSignal, pushing totally
        // unrelated messages (prayer time, event schedule, queue number) into
        // zakat_mal_consultation mode - which then injects the consultation hint into the system
        // prompt and sticks across turns via the context round-trip.
        $this->assertSame('general', $this->context()->detectMode($message, []));
    }

    public static function unrelatedDigitMessageProvider(): array
    {
        return [
            ['Assalamualaikum, saya mau tanya jadwal shalat jam 5 sore'],
            ['Ada acara kajian jam 7 malam ini gak?'],
            ['Nomor antrian saya 15, sudah dipanggil belum?'],
        ];
    }

    public function test_explicit_financial_keywords_still_trigger_the_mode(): void
    {
        $this->assertSame('zakat_mal_consultation', $this->context()->detectMode('Saya mau hitung zakat mal, gaji 10 juta', []));
        $this->assertSame('zakat_mal_consultation', $this->context()->detectMode('Tabungan saya 50 juta, hutang 10 juta', []));
    }

    public function test_bare_numeric_follow_up_stays_in_mode_once_already_consulting(): void
    {
        // A bare number reply mid-consultation ("50 juta") carries no financial keyword of its
        // own - continuity here comes from the previous turn's mode (the "stay in mode" branch),
        // not from $hasFinancialSignal, so removing the bare-digit check must not break this.
        $this->assertSame('zakat_mal_consultation', $this->context()->detectMode('50 juta', ['mode' => 'zakat_mal_consultation']));
        $this->assertSame('zakat_mal_consultation', $this->context()->detectMode('tidak ada hutang', ['mode' => 'zakat_mal_consultation']));
    }
}

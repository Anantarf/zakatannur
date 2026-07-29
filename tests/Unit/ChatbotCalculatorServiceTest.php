<?php

namespace Tests\Unit;

use App\Services\Chatbot\ChatbotCalculatorService;
use Tests\TestCase;

class ChatbotCalculatorServiceTest extends TestCase
{
    private function service(): ChatbotCalculatorService
    {
        return new ChatbotCalculatorService();
    }

    public function test_calculates_fitrah_for_digit_near_keyword(): void
    {
        $reply = $this->service()->calculateFitrah('Fitrah 4 orang berapa?')->reply;

        $this->assertStringContainsString('Fitrah untuk 4 orang', $reply);
        $this->assertStringContainsString('4 x Rp 50.000 = Rp 200.000', $reply);
    }

    public function test_calculates_fitrah_for_word_number_near_keyword(): void
    {
        $reply = $this->service()->calculateFitrah('Fitrah empat orang berapa?')->reply;

        $this->assertStringContainsString('Fitrah untuk 4 orang', $reply);
    }

    public function test_calculates_fidyah_for_digit_near_keyword(): void
    {
        $reply = $this->service()->calculateFidyah('Fidyah 7 hari berapa?')->reply;

        $this->assertStringContainsString('Fidyah untuk 7 hari', $reply);
        $this->assertStringContainsString('7 x Rp 30.000 = Rp 210.000', $reply);
    }

    public function test_fitrah_does_not_grab_an_unrelated_number_like_a_year(): void
    {
        // A blind "grab the first number in the message" fallback used to sit at the end of
        // extractNumberFromText() - "Fitrah tahun 2026 itu berapa ya per orang?" (asking about
        // this year's rate, not requesting a calculation) silently became "2026 orang" ->
        // Rp101.300.000, a wrong answer presented with full confidence and no error. This must now
        // ask for clarification instead of computing anything.
        $reply = $this->service()->calculateFitrah('Fitrah tahun 2026 itu berapa ya per orang?')->reply;

        $this->assertStringContainsString('Berapa orang yang mau dihitung fitrahnya?', $reply);
        $this->assertStringNotContainsString('2026', $reply);
    }

    public function test_fidyah_does_not_grab_an_unrelated_year_number(): void
    {
        $reply = $this->service()->calculateFidyah('Fidyah tahun 2026 per hari berapa ya?')->reply;

        $this->assertStringContainsString('Berapa hari fidyahnya?', $reply);
        $this->assertStringNotContainsString('2026', $reply);
    }

    public function test_asks_for_clarification_when_no_number_present(): void
    {
        $reply = $this->service()->calculateFitrah('Fitrah berapa ya?')->reply;

        $this->assertStringContainsString('Berapa orang yang mau dihitung fitrahnya?', $reply);
    }
}

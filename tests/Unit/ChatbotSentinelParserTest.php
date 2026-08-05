<?php

namespace Tests\Unit;

use App\Services\Chatbot\ChatbotSentinelParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotSentinelParserTest extends TestCase
{
    use RefreshDatabase;


    public function test_single_hitung_tag_is_replaced_with_computed_result(): void
    {
        $parser = app(ChatbotSentinelParser::class);

        $reply = $parser->parseAndCalculateSentinel(
            'Oke, datanya lengkap. [HITUNG:{"income_monthly":10000000,"savings":50000000}]'
        );

        $this->assertStringNotContainsString('[HITUNG:', $reply);
        $this->assertStringContainsString('[[HASIL]]', $reply);
    }

    public function test_multiple_hitung_tags_are_each_replaced_not_just_the_first(): void
    {
        // Not the intended prompt usage (the LLM is instructed to emit one combined tag), but the
        // parser must not silently leak a second tag's raw JSON to the user if it ever happens.
        $parser = app(ChatbotSentinelParser::class);

        $reply = $parser->parseAndCalculateSentinel(
            'Penghasilan: [HITUNG:{"income_monthly":10000000}] Tabungan: [HITUNG:{"savings":50000000}]'
        );

        $this->assertStringNotContainsString('[HITUNG:', $reply);
        $this->assertSame(2, substr_count($reply, '[[HASIL]]'));
    }

    public function test_income_only_hitung_renders_income_section_without_wealth_section(): void
    {
        $parser = app(ChatbotSentinelParser::class);

        $reply = $parser->parseAndCalculateSentinel('[HITUNG:{"income_monthly":10000000}]');

        $this->assertStringContainsString('[[HASIL]]', $reply);
        $this->assertStringContainsString('Estimasi Zakat Penghasilan', $reply);
        $this->assertStringNotContainsString('Estimasi Zakat Tabungan & Emas', $reply);
        $this->assertStringNotContainsString('Total estimasi zakat', $reply);
    }

    public function test_income_with_zero_wealth_values_still_hides_wealth_section(): void
    {
        $parser = app(ChatbotSentinelParser::class);

        $reply = $parser->parseAndCalculateSentinel('[HITUNG:{"income_monthly":10000000,"savings":0,"gold_gram":0,"debt":0}]');

        $this->assertStringContainsString('[[HASIL]]', $reply);
        $this->assertStringContainsString('Estimasi Zakat Penghasilan', $reply);
        $this->assertStringNotContainsString('Estimasi Zakat Tabungan & Emas', $reply);
        $this->assertStringNotContainsString('Total estimasi zakat', $reply);
    }

    public function test_debt_alone_asks_for_more_data_instead_of_a_misleading_wealth_section(): void
    {
        $parser = app(ChatbotSentinelParser::class);

        $reply = $parser->parseAndCalculateSentinel('[HITUNG:{"debt":5000000}]');

        $this->assertStringContainsString('Bisa sebutkan nominal penghasilan atau tabungannya', $reply);
        $this->assertStringNotContainsString('[[HASIL]]', $reply);
    }

    public function test_rupiah_formatted_string_value_is_rejected_instead_of_silently_truncated(): void
    {
        // (int) "10.000.000" is 10, not 10 million (PHP's (int) cast stops at the first invalid
        // character instead of rejecting the whole string) - this exact thousands-separator style
        // is what the bot itself uses in every reply, so an LLM slipping into it here is plausible.
        // Must be rejected outright, not silently computed into a confidently wrong "belum wajib
        // zakat" conclusion.
        $parser = app(ChatbotSentinelParser::class);

        $reply = $parser->parseAndCalculateSentinel('[HITUNG:{"income_monthly":"10.000.000","savings":50000000}]');

        $this->assertStringContainsString('kurang mengerti datanya', $reply);
        $this->assertStringNotContainsString('[[HASIL]]', $reply);
    }
}

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

    /**
     * @dataProvider hijackedByWrongCalculatorProvider
     */
    public function test_zakat_mal_requests_are_not_hijacked_into_the_wrong_calculator_or_a_canned_example(string $message): void
    {
        // Same bug class as the nishab regression above, found by auditing every other unguarded
        // branch in intent() for the same pattern: a zakat-mal calculation request that happens to
        // share a keyword with an unrelated fast-path intent gets short-circuited before it ever
        // reaches AI/the real calculator - so the user's actual numbers are silently ignored.
        // - calculate_fitrah_case/calculate_fidyah_case: "jiwa"/"puasa" + "hitung" + a digit is also
        //   how a zakat-mal question mentioning dependents or a missed fast can read, which routed
        //   it to the wrong calculator entirely (fitrah/fidyah instead of zakat mal).
        // - ask_zakat_mal_example: "contoh"/"skenario" + "hitung" matched even when the user gave
        //   their own real figures, returning a generic canned example instead of computing theirs.
        $this->assertNull($this->detector()->intent($message));
    }

    public static function hijackedByWrongCalculatorProvider(): array
    {
        return [
            ['Gaji saya 8 juta, tanggungan 3 jiwa, hitung zakat mal saya berapa?'],
            ['Saya batal puasa 3 hari karena sakit, terus gaji 8 juta hitung zakat mal saya gimana?'],
            ['Kasih contoh hitungan zakat mal untuk gaji saya 8 juta dong'],
            ['Ada contoh kasus zakat mal kalau gaji 10 juta, tabungan 20 juta?'],
        ];
    }

    /**
     * @dataProvider hijackedByRiceTotalProvider
     */
    public function test_personal_rice_harvest_questions_are_not_hijacked_into_mosque_rice_total(string $message): void
    {
        // ask_total_rice required only "beras" + a bare "berapa"/"kg" - no aggregate-implying
        // pairing like its ask_total_money/ask_total_summary siblings require ("total"/"terkumpul"/
        // "jumlah"), so a personal harvest question ("saya punya beras 800 kg... berapa zakat yang
        // harus dikeluarkan?") got answered with the mosque's aggregate rice total instead.
        $this->assertNull($this->detector()->intent($message));
    }

    public static function hijackedByRiceTotalProvider(): array
    {
        return [
            ['Panen saya 500 kg beras, hitungkan zakatnya berapa kg'],
            ['Saya punya beras 800 kg dari panen sendiri, berapa zakat yang harus dikeluarkan?'],
        ];
    }

    /**
     * @dataProvider hijackedByPaymentInfoProvider
     */
    public function test_zakat_mal_calculation_requests_are_not_hijacked_into_payment_info(string $message): void
    {
        // ask_payment_info matched on the bare substring "bayar zakat" (unguarded), which is also
        // contained in the very natural phrase "bayar zakat mal" - so a real calculation question
        // like "kapan sebaiknya saya bayar zakat mal saya, gaji saya 8 juta?" got answered with the
        // generic "how to pay" instructions instead of ever reaching AI.
        $this->assertNull($this->detector()->intent($message));
    }

    public static function hijackedByPaymentInfoProvider(): array
    {
        return [
            ['Kapan sebaiknya saya bayar zakat mal saya, gaji saya 8 juta?'],
            ['Kalau gaji saya 8 juta, apakah wajib bayar zakat mal bulan ini?'],
        ];
    }

    /**
     * @dataProvider hijackedByPublicDataFollowUpProvider
     */
    public function test_calculation_requests_are_not_hijacked_by_public_data_follow_up_when_prior_topic_was_public_data(string $message): void
    {
        // publicDataFollowUpIntent() - the follow-up routing used once the previous turn's topic was
        // public_data - is a near-duplicate of the guarded ask_total_summary/ask_latest_update checks
        // above, but was never guarded by !$looksLikeCalculationRequest itself. So once a session had
        // asked one public-data question (e.g. "total zakat terkumpul"), any later real calculation
        // question containing "semua"/"totalnya"/"kapan" got hijacked by this leftover context - even
        // though the message itself is an unrelated personal calculation request.
        $context = ['topic' => 'public_data', 'last_source' => 'public_data'];
        $this->assertNull($this->detector()->intent($message, $context));
    }

    public static function hijackedByPublicDataFollowUpProvider(): array
    {
        return [
            ['Saya sudah coba semua cara hitung sendiri tapi masih bingung, gaji saya 8 juta zakatnya berapa?'],
            ['Totalnya gaji saya 8 juta per bulan, kena zakat gak?'],
            ['Kapan sebaiknya saya keluarkan zakat mal kalau gaji saya 8 juta?'],
        ];
    }

    /**
     * @dataProvider hijackedNishabCalculationRequestProvider
     */
    public function test_personal_calculation_requests_are_not_hijacked_into_nishab_definition(string $message): void
    {
        // Regression for a reported bug: unlike the sibling ask_total_*/ask_zakat_mal_definition
        // checks, the ask_zakat_mal_nishab check wasn't guarded by !$looksLikeCalculationRequest.
        // A message with a concrete income figure ("Rp8.000.000") that also happens to contain
        // "nisab" + "apa"/"berapa" got short-circuited to the generic nisab-dan-haul KB answer
        // instead of ever reaching AI/calculation - so the user got the same canned explanation
        // no matter what number they gave.
        $this->assertNull($this->detector()->intent($message));
    }

    public static function hijackedNishabCalculationRequestProvider(): array
    {
        return [
            ['Penghasilan saya Rp8.000.000 per bulan. Apakah sudah mencapai nisab zakat penghasilan dan berapa zakat yang harus saya bayar?'],
            ['Gaji saya 7 juta, apa sudah kena nisab?'],
        ];
    }

    /**
     * @dataProvider assetSpecificNishabQuestionProvider
     */
    public function test_asset_specific_nishab_questions_are_not_hijacked_into_the_generic_definition(string $message): void
    {
        // Reported as a relevance gap, not a routing crash: "nishab zakat penghasilan berapa" has
        // no digit, so it's not a calculation request - it's a genuine information question. But the
        // fast path answered with the generic nisab-dan-haul entry (85 gram emas, 653 kg gabah, 40
        // ekor kambing - none of it about income), completely ignoring that the message names a
        // specific asset type ("penghasilan") which has its own KB entry with the actual Rupiah
        // figure the user is asking for. `ask_zakat_mal_nishab` unconditionally mapped to
        // 'nisab-dan-haul' regardless of message content (ChatbotOrchestrator.php:177) - the fix is
        // to let messages naming a specific asset fall through to AI/RAG instead, which retrieves
        // the asset-specific entry (already verified via chatbot:eval-rag).
        $this->assertNull($this->detector()->intent($message));
    }

    public static function assetSpecificNishabQuestionProvider(): array
    {
        return [
            ['Nishab zakat penghasilan berapa?'],
            ['Nisab tabungan berapa ya?'],
            ['Berapa nisab emas?'],
        ];
    }

    /**
     * @dataProvider assetSpecificDefinitionOrExampleQuestionProvider
     */
    public function test_asset_specific_definition_and_example_questions_are_not_hijacked_into_the_generic_zakat_mal_entry(string $message): void
    {
        // Same relevance gap as the nishab case above, found in the two sibling intents that also
        // unconditionally map to a single generic KB entry ('zakat-mal', ChatbotOrchestrator.php) -
        // "Apa itu zakat penghasilan?" and "Kasih contoh zakat emas dong" got the generic zakat-mal
        // definition/example instead of the far more relevant zakat-penghasilan/zakat-emas-perak
        // entries that actually name the asset asked about.
        $this->assertNull($this->detector()->intent($message));
    }

    public static function assetSpecificDefinitionOrExampleQuestionProvider(): array
    {
        return [
            ['Apa itu zakat penghasilan?'],
            ['Apa itu zakat emas?'],
            ['Pengertian zakat tabungan itu apa?'],
            ['Definisi zakat pertanian gimana?'],
            ['Kasih contoh zakat emas dong'],
            ['Ada contoh perhitungan zakat pertanian gak?'],
        ];
    }

    public function test_generic_definition_and_example_questions_still_resolve_to_their_intents(): void
    {
        // Must not regress: genuinely generic questions (no specific asset named) should still
        // fast-path as before.
        $this->assertSame('ask_zakat_mal_definition', $this->detector()->intent('Apa itu zakat mal?'));
        $this->assertSame('ask_zakat_mal_example', $this->detector()->intent('Contoh zakat mal gimana?'));
    }

    public function test_generic_nishab_questions_still_resolve_to_the_definition_intent(): void
    {
        // Must not regress: a genuinely generic nisab/haul question (no specific asset named) should
        // still fast-path to the nisab-dan-haul KB entry - that's the whole point of this intent.
        $this->assertSame('ask_zakat_mal_nishab', $this->detector()->intent('Nisab itu apa sih?'));
        $this->assertSame('ask_zakat_mal_nishab', $this->detector()->intent('Berapa nisab zakat mal?'));
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
        $this->assertSame('ask_total_money', $this->detector()->intent('Berapa total uang yang terkumpul?'));
        $this->assertSame('ask_total_people', $this->detector()->intent('Berapa total jiwa fitrah?'));
    }

    /**
     * @dataProvider hijackedByLooseDuplicateProvider
     */
    public function test_unrelated_questions_are_not_hijacked_by_bare_generic_words(string $message): void
    {
        // A second, looser copy of the ask_total_people/money/summary checks used to live further
        // down in intent() - it required no total/jumlah pairing at all for ask_total_people (bare
        // "orang", one of the most common words in Indonesian, was enough on its own) and dropped
        // the aggregate-implying pairing for ask_total_summary (bare "berapa" was enough). Neither
        // check should fire for these unrelated questions.
        $this->assertNotSame('ask_total_people', $this->detector()->intent($message));
        $this->assertNotSame('ask_total_summary', $this->detector()->intent($message));
    }

    public static function hijackedByLooseDuplicateProvider(): array
    {
        return [
            ['Orang tua saya sudah wafat, warisannya kena zakat gak?'],
            ['Zakat perdagangan itu dihitung dari modal atau omzet, berapa persennya?'],
        ];
    }

    /**
     * @dataProvider genericAnchorWordCasesProvider
     */
    public function test_unrelated_questions_are_not_hijacked_by_other_generic_anchor_words(string $message): void
    {
        // Narrowing pass across the whole detector (not just the two intents already covered
        // above): several other branches used bare, topically-neutral Indonesian words ("orang",
        // "hari", "harian", "paling besar"/"tertinggi", "rekening"/"transfer"/"cara bayar",
        // "kategori") as sole anchors, so they fired on unrelated questions that merely happened
        // to share that one word. All of these must now fall through to AI.
        $this->assertNull($this->detector()->intent($message));
    }

    public static function genericAnchorWordCasesProvider(): array
    {
        return [
            ['Total pengeluaran rumah tangga saya untuk orang tua per bulan itu ngurangin zakat gak?'],
            ['Saya mau hitung THR buat 3 orang karyawan, gimana ya caranya?'],
            ['Ada 5 hari libur lebaran ini, mau hitung cuti tambahan gimana?'],
            ['Petugas piket harian siapa aja ya minggu ini?'],
            ['Nisab yang paling besar itu emas atau uang tunai?'],
            ['Antara emas dan tabungan, mana yang nisabnya tertinggi?'],
            ['Kategori aset yang kena zakat itu apa aja?'],
            ['Jenis zakat mal yang paling sering ditanyakan apa ya?'],
            ['Ringkasan singkat soal zakat mal dong'],
            ['Rekening BCA punya saya kena zakat gak kalau isinya banyak?'],
            ['Cara bayar hutang riba itu gimana, ada hubungannya sama zakat?'],
        ];
    }

    public function test_narrowed_intents_still_resolve_for_their_genuine_phrasing(): void
    {
        // The narrowing above must not have swallowed the legitimate cases these intents exist
        // for - every keyword still needs at least one real, still-matching phrasing.
        $this->assertSame('open_chart', $this->detector()->intent('Lihat grafik harian'));
        $this->assertSame('open_summary', $this->detector()->intent('Buka ringkasan'));
        $this->assertSame('ask_categories', $this->detector()->intent('Kategori apa saja yang tercatat?'));
        $this->assertSame('ask_top_category', $this->detector()->intent('Kategori terbesar apa?'));
        $this->assertSame('ask_payment_info', $this->detector()->intent('Bagaimana cara bayar zakat?'));
    }

    public function test_location_and_contact_replies_do_not_contain_placeholder_data(): void
    {
        // Regression: ask_location/ask_contact used to hardcode example/template data ("Jl. Contoh
        // Alamat", "Kelurahan Maju", "Bapak Fulan", "0812-3456-7890") that was never replaced with
        // real information, so real users asking for the mosque's location/contact got confident,
        // fabricated answers. Zakky has no verified address/CP to share, so it defers to the
        // committee instead of stating specifics.
        $locationReply = $this->detector()->detect('Dimana lokasi masjid?')->reply;
        $contactReply = $this->detector()->detect('Boleh minta kontak panitia?')->reply;

        foreach ([$locationReply, $contactReply] as $reply) {
            $this->assertStringNotContainsStringIgnoringCase('Contoh Alamat', $reply);
            $this->assertStringNotContainsStringIgnoringCase('Kelurahan Maju', $reply);
            $this->assertStringNotContainsStringIgnoringCase('Bapak Fulan', $reply);
            $this->assertStringNotContainsString('0812-3456-7890', $reply);
        }
    }
}

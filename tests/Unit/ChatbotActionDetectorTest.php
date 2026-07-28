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
}

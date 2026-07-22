<?php

namespace Tests\Feature;

use App\Models\KnowledgeBase;
use Database\Seeders\KnowledgeBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeBaseSeederSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_runs_and_produces_expected_merged_entries(): void
    {
        (new KnowledgeBaseSeeder())->run();

        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'nisab-dan-haul']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'catatan-metodologi-zakat']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'zakat-penghasilan']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'zakat-emas-perak']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'cara-bayar-zakat']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'konfirmasi-pembayaran']);
        $this->assertDatabaseMissing('knowledge_bases', ['slug' => 'hitung-zakat-fitrah']);
        $this->assertDatabaseMissing('knowledge_bases', ['slug' => 'zakat-mal-definisi']);

        // New topics: one merged entry each, no leftover "hitung-*"/"case-*" duplicate slug.
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'zakat-pertanian-perkebunan']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'zakat-peternakan']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'zakat-properti-sewa']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'zakat-saham-investasi-reksadana']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'zakat-warisan']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'cara-zakky-menganalisis-kasus']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'zakat-aset-pribadi']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'zakat-kendaraan']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'zakat-rumah-pribadi']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'zakat-hadiah-hibah']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'zakat-uang-pesangon']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'zakat-dana-pensiun']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'zakat-piutang']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'zakat-harta-campuran']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'pembayaran-cek']);
        $this->assertDatabaseHas('knowledge_bases', ['slug' => 'batas-hitung-zakat-mal-lanjutan']);
        $this->assertDatabaseMissing('knowledge_bases', ['slug' => 'case-warisan-uang']);
        $this->assertDatabaseMissing('knowledge_bases', ['slug' => 'metode-pembayaran']);

        // "hitung-*" halves for pertanian/peternakan/properti-sewa/saham are merged back into
        // their single definisi entry (same pattern as zakat-fitrah/zakat-penghasilan), and the
        // redundant zakat-vs-infaq / dashboard-publik trio are collapsed to one entry each - see
        // KnowledgeRetriever::search()'s top-2 retrieval, which can't split a topic across a
        // "definisi" and "hitung" entry without risking only half showing up.
        $this->assertDatabaseMissing('knowledge_bases', ['slug' => 'hitung-zakat-pertanian']);
        $this->assertDatabaseMissing('knowledge_bases', ['slug' => 'hitung-zakat-peternakan']);
        $this->assertDatabaseMissing('knowledge_bases', ['slug' => 'hitung-zakat-properti-sewa']);
        $this->assertDatabaseMissing('knowledge_bases', ['slug' => 'hitung-zakat-saham']);
        $this->assertDatabaseMissing('knowledge_bases', ['slug' => 'zakat-atau-infaq']);
        $this->assertDatabaseMissing('knowledge_bases', ['slug' => 'ringkasan-penerimaan']);
        $this->assertDatabaseMissing('knowledge_bases', ['slug' => 'transparansi-zakat']);

        $zakatFitrah = KnowledgeBase::where('slug', 'zakat-fitrah')->first();
        $this->assertNotNull($zakatFitrah);
        $this->assertStringContainsString('Rp 50.000', $zakatFitrah->answer);
        $this->assertStringContainsString('Rp 200.000', strip_tags(str_replace('**', '', $zakatFitrah->answer)));
        $this->assertStringContainsString('10 kg', $zakatFitrah->answer);

        $analysisAnchor = KnowledgeBase::where('slug', 'cara-zakky-menganalisis-kasus')->first();
        $this->assertNotNull($analysisAnchor);
        $this->assertStringContainsString('menganalisis secara bertahap', $analysisAnchor->answer);

        $allAnswers = KnowledgeBase::query()->pluck('answer')->implode("\n");
        $this->assertStringNotContainsString('â', $allAnswers);
        $this->assertStringNotContainsString('Ã', $allAnswers);
        $this->assertStringNotContainsString('[Kalkulator Zakat]', $allAnswers);
        $this->assertStringNotContainsString('website kami (jika tersedia)', $allAnswers);
        $this->assertSame(0, KnowledgeBase::query()->whereJsonLength('actions', '>', 0)->count());

        // firstOrCreate must not clobber an existing (e.g. admin-edited) row on re-run.
        $zakatFitrah->update(['answer' => 'EDITED BY ADMIN']);
        (new KnowledgeBaseSeeder())->run();
        $this->assertSame('EDITED BY ADMIN', $zakatFitrah->fresh()->answer);
    }
}

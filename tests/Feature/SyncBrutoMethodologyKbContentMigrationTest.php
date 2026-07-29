<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SyncBrutoMethodologyKbContentMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider staleSlugProvider
     */
    public function test_migration_overwrites_pre_bab_6_2_stale_answer_text(string $slug): void
    {
        // Simulates the real-world scenario this migration exists for: a database that already
        // had this row seeded BEFORE the Bab 6.2 content fix. KnowledgeBaseSeeder's firstOrCreate
        // would never update it on a re-seed (by design, to protect admin edits) - only this
        // targeted migration reaches it.
        DB::table('knowledge_bases')->insert([
            'slug' => $slug,
            'title' => 'placeholder',
            'answer' => 'Teks lama yang ambigu soal bruto/netto, silakan konfirmasi ke panitia atau ustadz.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (require database_path('migrations/2026_07_29_010000_sync_bruto_methodology_kb_content.php'))->up();

        $answer = DB::table('knowledge_bases')->where('slug', $slug)->value('answer');

        $this->assertStringContainsString('bruto', $answer);
        $this->assertStringNotContainsString('ambigu', $answer);
    }

    public static function staleSlugProvider(): array
    {
        return [
            ['catatan-metodologi-zakat'],
            ['zakat-penghasilan-potongan-pajak-bpjs'],
        ];
    }

    public function test_migration_is_a_no_op_when_the_slug_does_not_exist(): void
    {
        // The table is empty at this point (RefreshDatabase ran migrations, no seeder) - up()
        // must not error or insert a row for a slug that was never seeded.
        (require database_path('migrations/2026_07_29_010000_sync_bruto_methodology_kb_content.php'))->up();

        $this->assertSame(0, DB::table('knowledge_bases')->count());
    }
}

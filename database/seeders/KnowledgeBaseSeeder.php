<?php

namespace Database\Seeders;

use App\Models\KnowledgeBase;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $entries = config('zakky_knowledge', []);

        foreach ($entries as $entry) {
            KnowledgeBase::updateOrCreate(
                ['slug' => $entry['id']],
                [
                    'title' => $entry['title'] ?? '',
                    'keywords' => $entry['keywords'] ?? [],
                    'answer' => $entry['answer'] ?? '',
                    'source_label' => $entry['source_label'] ?? null,
                    'actions' => $entry['actions'] ?? [],
                    'is_active' => true,
                ]
            );
        }
    }
}

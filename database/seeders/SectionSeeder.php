<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $qualitat = Section::factory()->create([
            'name' => 'QUALITAT',
            'description' => 'Comprovacions de qualitat de l\'equipament',
            'order' => 1,
        ]);

        $questions = [
            'L\'acabat superficial és correcte?',
            'Les connexions elèctriques estan ben fixades?',
            'L\'etiquetatge és correcte i llegible?',
            'Les dimensions compleixen l\'especificació?',
        ];

        foreach ($questions as $index => $text) {
            Question::factory()->create([
                'section_id' => $qualitat->id,
                'text' => $text,
                'order' => $index + 1,
                'is_required' => true,
            ]);
        }
    }
}

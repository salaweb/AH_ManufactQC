<?php

namespace Database\Seeders;

use App\Enums\QuestionCategory;
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
            ['text' => 'L\'acabat superficial és correcte?', 'category' => QuestionCategory::Estetica],
            ['text' => 'L\'etiquetatge és correcte i llegible?', 'category' => QuestionCategory::Estetica],
            ['text' => 'Les dimensions compleixen l\'especificació?', 'category' => QuestionCategory::FuncionalMecanica],
            ['text' => 'Les connexions elèctriques estan ben fixades?', 'category' => QuestionCategory::Electronica],
        ];

        foreach ($questions as $index => $question) {
            Question::factory()->create([
                'section_id' => $qualitat->id,
                'text' => $question['text'],
                'category' => $question['category'],
                'order' => $index + 1,
                'is_required' => true,
            ]);
        }
    }
}

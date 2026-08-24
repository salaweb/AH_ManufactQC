<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'text' => $this->faker->sentence().'?',
            'order' => 0,
            'is_required' => true,
        ];
    }
}

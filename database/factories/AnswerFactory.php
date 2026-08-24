<?php

namespace Database\Factories;

use App\Enums\AnswerResponse;
use App\Models\Answer;
use App\Models\Equipment;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Answer>
 */
class AnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipment_id' => Equipment::factory(),
            'question_id' => Question::factory(),
            'response' => $this->faker->randomElement(AnswerResponse::cases()),
            'language_chosen' => $this->faker->randomElement(['ca', 'es']),
        ];
    }
}

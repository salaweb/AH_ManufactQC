<?php

namespace Database\Factories;

use App\Models\Defect;
use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Defect>
 */
class DefectFactory extends Factory
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
            'answer_id' => null,
            'tipo' => $this->faker->randomElement(['visual', 'dimensional', 'funcional']),
            'observation' => $this->faker->sentence(),
            'responsibility' => $this->faker->randomElement(['producció', 'proveïdor', 'disseny']),
            'actions' => $this->faker->sentence(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => $this->faker->unique()->numerify('1400C####.00'),
            'family' => $this->faker->randomElement(['DB2', 'DB3', 'DB4']),
            'description' => $this->faker->sentence(),
            'observations' => $this->faker->optional()->paragraph(),
        ];
    }
}

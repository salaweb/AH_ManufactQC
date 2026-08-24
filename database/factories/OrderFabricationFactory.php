<?php

namespace Database\Factories;

use App\Models\OrderFabrication;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderFabrication>
 */
class OrderFabricationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'number' => $this->faker->unique()->numerify(now()->format('Y').'/##/#######'),
        ];
    }
}

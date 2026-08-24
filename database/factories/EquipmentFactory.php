<?php

namespace Database\Factories;

use App\Enums\EquipmentStatus;
use App\Models\Equipment;
use App\Models\OrderFabrication;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
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
            'order_fabrication_id' => fn (array $attributes) => OrderFabrication::factory()
                ->create(['project_id' => $attributes['project_id']])->id,
            'serie_number' => $this->faker->unique()->bothify('SN-#####'),
            'observations' => $this->faker->optional()->sentence(),
            'status' => EquipmentStatus::Pending,
            'checked_at' => null,
        ];
    }
}

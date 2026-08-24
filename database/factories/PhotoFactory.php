<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Photo>
 */
class PhotoFactory extends Factory
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
            'path' => 'photos/'.$this->faker->uuid().'.jpg',
            'uploaded_at' => now(),
        ];
    }
}

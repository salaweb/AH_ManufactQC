<?php

namespace Database\Factories;

use App\Models\DescriptionTag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DescriptionTag>
 */
class DescriptionTagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->bothify('TAG-????'),
        ];
    }
}

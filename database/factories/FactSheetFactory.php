<?php

namespace Database\Factories;

use App\Models\FactSheet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FactSheet>
 */
class FactSheetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'allowed_roles' => null,
        ];
    }
}

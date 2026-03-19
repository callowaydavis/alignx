<?php

namespace Database\Factories;

use App\Models\ComponentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComponentType>
 */
class ComponentTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'color' => $this->faker->randomElement(['blue', 'purple', 'green', 'orange', 'teal', 'yellow', 'red', 'gray']),
            'is_system' => false,
        ];
    }
}

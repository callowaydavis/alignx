<?php

namespace Database\Factories;

use App\Models\Component;
use App\Models\ComponentRelationship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComponentRelationship>
 */
class ComponentRelationshipFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source_component_id' => Component::factory(),
            'target_component_id' => Component::factory(),
            'relationship_type' => $this->faker->optional()->randomElement(['Uses', 'Owns', 'Provides', 'Connects', 'Depends On', 'Contains']),
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}

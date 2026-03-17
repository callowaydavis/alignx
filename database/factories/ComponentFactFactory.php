<?php

namespace Database\Factories;

use App\Models\Component;
use App\Models\ComponentFact;
use App\Models\FactDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComponentFact>
 */
class ComponentFactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'component_id' => Component::factory(),
            'fact_definition_id' => FactDefinition::factory(),
            'value' => $this->faker->word(),
        ];
    }
}

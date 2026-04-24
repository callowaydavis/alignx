<?php

namespace Database\Factories;

use App\Models\Attribute;
use App\Models\Component;
use App\Models\ComponentFact;
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
            'attribute_id' => Attribute::factory(),
            'value' => $this->faker->word(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\FactFieldType;
use App\Models\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attribute>
 */
class AttributeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'field_type' => $this->faker->randomElement(FactFieldType::cases())->value,
            'options' => null,
        ];
    }
}

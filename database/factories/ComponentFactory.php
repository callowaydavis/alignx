<?php

namespace Database\Factories;

use App\Enums\ComponentType;
use App\Enums\LifecycleStage;
use App\Models\Component;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Component>
 */
class ComponentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(ComponentType::cases())->value,
            'is_active' => true,
            'description' => $this->faker->optional()->sentence(),
            'lifecycle_stage' => $this->faker->optional()->randomElement(LifecycleStage::cases())?->value,
            'lifecycle_start_date' => $this->faker->optional()->date(),
            'lifecycle_end_date' => $this->faker->optional()->date(),
        ];
    }

    public function withParent(Component $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
        ]);
    }
}

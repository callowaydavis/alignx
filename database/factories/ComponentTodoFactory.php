<?php

namespace Database\Factories;

use App\Enums\TodoCategory;
use App\Enums\TodoStatus;
use App\Models\Component;
use App\Models\ComponentTodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComponentTodo>
 */
class ComponentTodoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'component_id' => Component::factory(),
            'condition' => fake()->sentence(),
            'category' => fake()->randomElement(TodoCategory::cases())->value,
            'status' => TodoStatus::Pending->value,
            'accepted_by' => null,
            'acceptance_notes' => null,
            'due_date' => null,
            'completed_by' => null,
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TodoStatus::Completed->value,
            'completed_at' => now(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'ad_group' => null,
            'description' => $this->faker->optional()->sentence(),
        ];
    }

    public function withAdGroup(): static
    {
        return $this->state(fn () => [
            'ad_group' => 'GRP_'.strtoupper($this->faker->unique()->word()),
        ]);
    }
}

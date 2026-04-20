<?php

namespace Database\Factories;

use App\Models\Component;
use App\Models\ComponentDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComponentDocument>
 */
class ComponentDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = $this->faker->word().'.pdf';

        return [
            'component_id' => Component::factory(),
            'original_filename' => $filename,
            'stored_path' => 'documents/'.$this->faker->uuid().'.pdf',
            'disk' => 'local',
            'mime_type' => 'application/pdf',
            'file_size' => $this->faker->numberBetween(1024, 5242880),
            'uploaded_by' => User::factory(),
        ];
    }
}

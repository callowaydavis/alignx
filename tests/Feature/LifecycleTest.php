<?php

namespace Tests\Feature;

use App\Enums\ComponentType;
use App\Enums\LifecycleStage;
use App\Models\Component;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    // --- Web routes ---

    public function test_create_component_with_lifecycle_stage(): void
    {
        $response = $this->post(route('components.store'), [
            'name' => 'My App',
            'type' => ComponentType::Application->value,
            'lifecycle_stage' => LifecycleStage::Active->value,
            'lifecycle_start_date' => '2024-01-01',
            'lifecycle_end_date' => '2025-12-31',
        ]);

        $response->assertRedirect();

        $component = Component::query()->where('name', 'My App')->first();
        $this->assertNotNull($component);
        $this->assertEquals('Active', $component->lifecycle_stage->value);
        $this->assertEquals('2024-01-01', $component->lifecycle_start_date->toDateString());
        $this->assertEquals('2025-12-31', $component->lifecycle_end_date->toDateString());
    }

    public function test_create_component_without_lifecycle_stage(): void
    {
        $response = $this->post(route('components.store'), [
            'name' => 'My App',
            'type' => ComponentType::Application->value,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('components', [
            'name' => 'My App',
            'lifecycle_stage' => null,
        ]);
    }

    public function test_lifecycle_stage_invalid_value_fails_validation(): void
    {
        $response = $this->post(route('components.store'), [
            'name' => 'My App',
            'type' => ComponentType::Application->value,
            'lifecycle_stage' => 'Invalid Stage',
        ]);

        $response->assertSessionHasErrors('lifecycle_stage');
    }

    public function test_lifecycle_end_date_before_start_date_fails_validation(): void
    {
        $response = $this->post(route('components.store'), [
            'name' => 'My App',
            'type' => ComponentType::Application->value,
            'lifecycle_stage' => LifecycleStage::Active->value,
            'lifecycle_start_date' => '2025-01-01',
            'lifecycle_end_date' => '2024-01-01',
        ]);

        $response->assertSessionHasErrors('lifecycle_end_date');
    }

    public function test_update_component_lifecycle_stage(): void
    {
        $component = Component::factory()->create(['lifecycle_stage' => null]);

        $response = $this->put(route('components.update', $component), [
            'name' => $component->name,
            'type' => $component->type->value,
            'lifecycle_stage' => LifecycleStage::PhaseOut->value,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('components', [
            'id' => $component->id,
            'lifecycle_stage' => 'Phase Out',
        ]);
    }

    public function test_index_filters_by_lifecycle_stage(): void
    {
        Component::factory()->create(['name' => 'Active App', 'lifecycle_stage' => LifecycleStage::Active->value]);
        Component::factory()->create(['name' => 'Plan App', 'lifecycle_stage' => LifecycleStage::Plan->value]);

        $response = $this->get(route('components.index', ['lifecycle_stage' => 'Active']));

        $response->assertStatus(200);
        $response->assertSee('Active App');
        $response->assertDontSee('Plan App');
    }

    public function test_lifecycle_badge_shown_on_show_page(): void
    {
        $component = Component::factory()->create(['lifecycle_stage' => LifecycleStage::Active->value]);

        $response = $this->get(route('components.show', $component));

        $response->assertStatus(200);
        $response->assertSee('Active');
    }

    // --- API routes ---

    public function test_api_create_component_with_lifecycle(): void
    {
        $response = $this->postJson('/api/v1/components', [
            'name' => 'API App',
            'type' => ComponentType::Application->value,
            'lifecycle_stage' => LifecycleStage::Plan->value,
            'lifecycle_start_date' => '2025-01-01',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.lifecycle_stage', 'Plan');
        $response->assertJsonPath('data.lifecycle_start_date', '2025-01-01');
    }

    public function test_api_filter_components_by_lifecycle_stage(): void
    {
        Component::factory()->create(['name' => 'Active App', 'lifecycle_stage' => LifecycleStage::Active->value]);
        Component::factory()->create(['name' => 'EOL App', 'lifecycle_stage' => LifecycleStage::EndOfLife->value]);

        $response = $this->getJson('/api/v1/components?lifecycle_stage=Active');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Active App');
    }

    public function test_api_component_resource_includes_lifecycle_fields(): void
    {
        $component = Component::factory()->create([
            'lifecycle_stage' => LifecycleStage::Active->value,
            'lifecycle_start_date' => '2024-06-01',
            'lifecycle_end_date' => null,
        ]);

        $response = $this->getJson("/api/v1/components/{$component->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['lifecycle_stage', 'lifecycle_start_date', 'lifecycle_end_date']]);
        $response->assertJsonPath('data.lifecycle_stage', 'Active');
        $response->assertJsonPath('data.lifecycle_start_date', '2024-06-01');
        $response->assertJsonPath('data.lifecycle_end_date', null);
    }
}

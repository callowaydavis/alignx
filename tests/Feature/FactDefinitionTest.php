<?php

namespace Tests\Feature;

use App\Enums\ComponentType;
use App\Enums\FactFieldType;
use App\Models\FactDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactDefinitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_fact_definitions_index_page_loads(): void
    {
        FactDefinition::factory()->count(3)->create();

        $this->get(route('fact-definitions.index'))->assertOk()->assertViewIs('fact-definitions.index');
    }

    public function test_fact_definitions_create_page_loads(): void
    {
        $this->get(route('fact-definitions.create'))->assertOk()->assertViewIs('fact-definitions.create');
    }

    public function test_can_create_fact_definition(): void
    {
        $response = $this->post(route('fact-definitions.store'), [
            'name' => 'Operating System',
            'field_type' => FactFieldType::Text->value,
        ]);

        $response->assertRedirect(route('fact-definitions.index'));
        $this->assertDatabaseHas('fact_definitions', ['name' => 'Operating System', 'field_type' => 'text']);
    }

    public function test_can_create_fact_definition_scoped_to_component_types(): void
    {
        $this->post(route('fact-definitions.store'), [
            'name' => 'RAM',
            'field_type' => FactFieldType::Number->value,
            'component_types' => [ComponentType::ItComponent->value],
        ])->assertRedirect();

        $factDef = FactDefinition::query()->where('name', 'RAM')->firstOrFail();
        $this->assertContains('IT Component', $factDef->component_types);
    }

    public function test_can_create_select_fact_definition_with_options(): void
    {
        $this->post(route('fact-definitions.store'), [
            'name' => 'Status',
            'field_type' => FactFieldType::Select->value,
            'options' => ['Active', 'Inactive', 'Deprecated'],
        ])->assertRedirect();

        $factDef = FactDefinition::query()->where('name', 'Status')->firstOrFail();
        $this->assertEquals(['Active', 'Inactive', 'Deprecated'], $factDef->options);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->post(route('fact-definitions.store'), [])->assertSessionHasErrors(['name', 'field_type']);
    }

    public function test_create_validates_field_type_enum(): void
    {
        $this->post(route('fact-definitions.store'), [
            'name' => 'Test',
            'field_type' => 'invalid',
        ])->assertSessionHasErrors(['field_type']);
    }

    public function test_fact_definition_edit_page_loads(): void
    {
        $factDef = FactDefinition::factory()->create();

        $this->get(route('fact-definitions.edit', $factDef))->assertOk()->assertViewIs('fact-definitions.edit');
    }

    public function test_can_update_fact_definition(): void
    {
        $factDef = FactDefinition::factory()->create(['name' => 'Old Name']);

        $this->put(route('fact-definitions.update', $factDef), [
            'name' => 'New Name',
            'field_type' => FactFieldType::Boolean->value,
        ])->assertRedirect(route('fact-definitions.index'));

        $this->assertDatabaseHas('fact_definitions', ['id' => $factDef->id, 'name' => 'New Name']);
    }

    public function test_can_delete_fact_definition(): void
    {
        $factDef = FactDefinition::factory()->create();

        $this->delete(route('fact-definitions.destroy', $factDef))->assertRedirect(route('fact-definitions.index'));
        $this->assertDatabaseMissing('fact_definitions', ['id' => $factDef->id]);
    }

    public function test_api_can_list_fact_definitions(): void
    {
        FactDefinition::factory()->count(5)->create();

        $this->getJson('/api/v1/fact-definitions')->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_api_filters_fact_definitions_by_component_type(): void
    {
        FactDefinition::factory()->create([
            'name' => 'RAM',
            'component_types' => [ComponentType::ItComponent->value],
        ]);
        FactDefinition::factory()->create([
            'name' => 'Global Fact',
            'component_types' => null,
        ]);

        $response = $this->getJson('/api/v1/fact-definitions?component_type=IT+Component');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_api_can_create_fact_definition(): void
    {
        $this->postJson('/api/v1/fact-definitions', [
            'name' => 'CPU Cores',
            'field_type' => FactFieldType::Number->value,
        ])->assertCreated()->assertJsonPath('data.name', 'CPU Cores');
    }
}

<?php

namespace Tests\Feature;

use App\Enums\FactFieldType;
use App\Models\Attribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_fact_definitions_index_page_loads(): void
    {
        Attribute::factory()->count(3)->create();

        $this->get(route('attributes.index'))->assertOk()->assertViewIs('attributes.index');
    }

    public function test_fact_definitions_create_page_loads(): void
    {
        $this->get(route('attributes.create'))->assertOk()->assertViewIs('attributes.create');
    }

    public function test_can_create_fact_definition(): void
    {
        $response = $this->post(route('attributes.store'), [
            'name' => 'Operating System',
            'field_type' => FactFieldType::Text->value,
        ]);

        $response->assertRedirect(route('attributes.index'));
        $this->assertDatabaseHas('attributes', ['name' => 'Operating System', 'field_type' => 'text']);
    }

    public function test_can_create_select_fact_definition_with_options(): void
    {
        $this->post(route('attributes.store'), [
            'name' => 'Status',
            'field_type' => FactFieldType::Select->value,
            'options' => ['Active', 'Inactive', 'Deprecated'],
        ])->assertRedirect();

        $factDef = Attribute::query()->where('name', 'Status')->firstOrFail();
        $this->assertEquals(['Active', 'Inactive', 'Deprecated'], $factDef->options);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->post(route('attributes.store'), [])->assertSessionHasErrors(['name', 'field_type']);
    }

    public function test_create_validates_field_type_enum(): void
    {
        $this->post(route('attributes.store'), [
            'name' => 'Test',
            'field_type' => 'invalid',
        ])->assertSessionHasErrors(['field_type']);
    }

    public function test_fact_definition_edit_page_loads(): void
    {
        $factDef = Attribute::factory()->create();

        $this->get(route('attributes.edit', $factDef))->assertOk()->assertViewIs('attributes.edit');
    }

    public function test_can_update_fact_definition(): void
    {
        $factDef = Attribute::factory()->create(['name' => 'Old Name']);

        $this->put(route('attributes.update', $factDef), [
            'name' => 'New Name',
            'field_type' => FactFieldType::Boolean->value,
        ])->assertRedirect(route('attributes.index'));

        $this->assertDatabaseHas('attributes', ['id' => $factDef->id, 'name' => 'New Name']);
    }

    public function test_can_delete_fact_definition(): void
    {
        $factDef = Attribute::factory()->create();

        $this->delete(route('attributes.destroy', $factDef))->assertRedirect(route('attributes.index'));
        $this->assertDatabaseMissing('attributes', ['id' => $factDef->id]);
    }

    public function test_api_can_list_fact_definitions(): void
    {
        Attribute::factory()->count(5)->create();

        $this->getJson('/api/v1/attributes')->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_api_can_create_fact_definition(): void
    {
        $this->postJson('/api/v1/attributes', [
            'name' => 'CPU Cores',
            'field_type' => FactFieldType::Number->value,
        ])->assertCreated()->assertJsonPath('data.name', 'CPU Cores');
    }
}

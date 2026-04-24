<?php

namespace Tests\Feature;

use App\Enums\ComponentType;
use App\Models\Attribute;
use App\Models\Component;
use App\Models\ComponentFact;
use App\Models\ComponentRelationship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    // --- Web routes ---

    public function test_components_index_page_loads(): void
    {
        Component::factory()->count(3)->create();

        $this->get(route('components.index'))->assertOk()->assertViewIs('components.index');
    }

    public function test_components_index_filters_by_type(): void
    {
        Component::factory()->create(['type' => ComponentType::Application->value, 'name' => 'App One']);
        Component::factory()->create(['type' => ComponentType::ItComponent->value, 'name' => 'Server One']);

        $response = $this->get(route('components.index', ['type' => 'Application']));

        $response->assertOk()->assertSee('App One')->assertDontSee('Server One');
    }

    public function test_component_create_page_loads(): void
    {
        $this->get(route('components.create'))->assertOk()->assertViewIs('components.create');
    }

    public function test_can_create_component(): void
    {
        $response = $this->post(route('components.store'), [
            'name' => 'My Application',
            'type' => ComponentType::Application->value,
            'description' => 'A test application',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('components', ['name' => 'My Application', 'type' => 'Application']);
    }

    public function test_create_component_validates_required_fields(): void
    {
        $this->post(route('components.store'), [])->assertSessionHasErrors(['name', 'type']);
    }

    public function test_create_component_validates_type_enum(): void
    {
        $this->post(route('components.store'), [
            'name' => 'Test',
            'type' => 'InvalidType',
        ])->assertSessionHasErrors(['type']);
    }

    public function test_component_show_page_loads(): void
    {
        $component = Component::factory()->create();

        $this->get(route('components.show', $component))->assertOk()->assertViewIs('components.show');
    }

    public function test_component_edit_page_loads(): void
    {
        $component = Component::factory()->create();

        $this->get(route('components.edit', $component))->assertOk()->assertViewIs('components.edit');
    }

    public function test_can_update_component(): void
    {
        $component = Component::factory()->create(['name' => 'Old Name']);

        $this->put(route('components.update', $component), [
            'name' => 'New Name',
            'type' => ComponentType::Provider->value,
        ])->assertRedirect(route('components.show', $component));

        $this->assertDatabaseHas('components', ['id' => $component->id, 'name' => 'New Name', 'type' => 'Provider']);
    }

    public function test_can_delete_component(): void
    {
        $component = Component::factory()->create();

        $this->delete(route('components.destroy', $component))->assertRedirect(route('components.index'));

        $this->assertDatabaseMissing('components', ['id' => $component->id]);
    }

    // --- Relationships ---

    public function test_can_add_relationship(): void
    {
        $source = Component::factory()->create();
        $target = Component::factory()->create();

        $this->post(route('components.relationships.store', $source), [
            'target_component_id' => $target->id,
            'relationship_type' => 'Uses',
        ])->assertRedirect(route('components.show', $source));

        $this->assertDatabaseHas('component_relationships', [
            'source_component_id' => $source->id,
            'target_component_id' => $target->id,
            'relationship_type' => 'Uses',
        ]);
    }

    public function test_can_delete_relationship(): void
    {
        $rel = ComponentRelationship::factory()->create();

        $this->delete(route('components.relationships.destroy', [$rel->sourceComponent, $rel]))
            ->assertRedirect();

        $this->assertDatabaseMissing('component_relationships', ['id' => $rel->id]);
    }

    // --- Facts ---

    public function test_can_add_fact_to_component(): void
    {
        $component = Component::factory()->create(['type' => ComponentType::ItComponent->value]);
        $factDef = Attribute::factory()->create(['name' => 'Operating System']);

        $this->post(route('components.facts.store', $component), [
            'attribute_id' => $factDef->id,
            'value' => 'Ubuntu 22.04',
        ])->assertRedirect(route('components.show', $component));

        $this->assertDatabaseHas('component_facts', [
            'component_id' => $component->id,
            'attribute_id' => $factDef->id,
            'value' => 'Ubuntu 22.04',
        ]);
    }

    public function test_adding_same_fact_twice_updates_value(): void
    {
        $component = Component::factory()->create();
        $factDef = Attribute::factory()->create();

        $this->post(route('components.facts.store', $component), [
            'attribute_id' => $factDef->id,
            'value' => 'First',
        ]);

        $this->post(route('components.facts.store', $component), [
            'attribute_id' => $factDef->id,
            'value' => 'Updated',
        ]);

        $this->assertDatabaseCount('component_facts', 1);
        $this->assertDatabaseHas('component_facts', ['value' => 'Updated']);
    }

    public function test_can_delete_fact(): void
    {
        $fact = ComponentFact::factory()->create();

        $this->delete(route('components.facts.destroy', [$fact->component, $fact->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('component_facts', ['id' => $fact->id]);
    }

    // --- API routes ---

    public function test_api_can_list_components(): void
    {
        Component::factory()->count(3)->create();

        $this->getJson('/api/v1/components')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_api_can_create_component(): void
    {
        $response = $this->postJson('/api/v1/components', [
            'name' => 'API Component',
            'type' => ComponentType::DataObject->value,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('components', ['name' => 'API Component']);
    }

    public function test_api_can_show_component(): void
    {
        $component = Component::factory()->create();

        $this->getJson("/api/v1/components/{$component->id}")->assertOk()->assertJsonPath('data.id', $component->id);
    }

    public function test_api_can_update_component(): void
    {
        $component = Component::factory()->create();

        $this->putJson("/api/v1/components/{$component->id}", [
            'name' => 'Updated',
            'type' => ComponentType::Process->value,
        ])->assertOk()->assertJsonPath('data.name', 'Updated');
    }

    public function test_api_can_delete_component(): void
    {
        $component = Component::factory()->create();

        $this->deleteJson("/api/v1/components/{$component->id}")->assertNoContent();
        $this->assertDatabaseMissing('components', ['id' => $component->id]);
    }

    public function test_api_filters_components_by_type(): void
    {
        Component::factory()->create(['type' => ComponentType::Application->value]);
        Component::factory()->create(['type' => ComponentType::ItComponent->value]);

        $this->getJson('/api/v1/components?type=Application')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_deleting_component_cascades_relationships_and_facts(): void
    {
        $component = Component::factory()->create();
        ComponentFact::factory()->create(['component_id' => $component->id]);
        ComponentRelationship::factory()->create(['source_component_id' => $component->id]);

        $this->deleteJson("/api/v1/components/{$component->id}")->assertNoContent();

        $this->assertDatabaseCount('component_facts', 0);
        $this->assertDatabaseCount('component_relationships', 0);
    }

    // --- Subcomponent tests ---

    public function test_can_create_subcomponent_with_parent_id(): void
    {
        $parent = Component::factory()->create();

        $response = $this->post(route('components.store'), [
            'name' => 'Workflow Module',
            'type' => ComponentType::Application->value,
            'parent_id' => $parent->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('components', [
            'name' => 'Workflow Module',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_show_page_displays_subcomponents_panel(): void
    {
        $parent = Component::factory()->create();
        Component::factory()->withParent($parent)->create(['name' => 'Sub One']);

        $this->get(route('components.show', $parent))
            ->assertOk()
            ->assertSee('Subcomponents')
            ->assertSee('Sub One');
    }

    public function test_show_page_displays_parent_breadcrumb_on_subcomponent(): void
    {
        $parent = Component::factory()->create(['name' => 'Hyland OnBase']);
        $sub = Component::factory()->withParent($parent)->create(['name' => 'Workflow']);

        $this->get(route('components.show', $sub))
            ->assertOk()
            ->assertSee('Hyland OnBase');
    }

    public function test_index_excludes_subcomponents_by_default(): void
    {
        $parent = Component::factory()->create(['name' => 'Root Component']);
        Component::factory()->withParent($parent)->create(['name' => 'Sub Component']);

        $this->get(route('components.index'))
            ->assertOk()
            ->assertSee('Root Component')
            ->assertDontSee('Sub Component');
    }

    public function test_index_includes_subcomponents_when_filter_active(): void
    {
        $parent = Component::factory()->create(['name' => 'Root Component']);
        Component::factory()->withParent($parent)->create(['name' => 'Sub Component']);

        $this->get(route('components.index', ['include_subcomponents' => '1']))
            ->assertOk()
            ->assertSee('Root Component')
            ->assertSee('Sub Component');
    }

    public function test_deleting_parent_nullifies_subcomponent_parent_id(): void
    {
        $parent = Component::factory()->create();
        $sub = Component::factory()->withParent($parent)->create();

        $this->assertDatabaseHas('components', ['id' => $sub->id, 'parent_id' => $parent->id]);

        $parent->delete();

        $this->assertDatabaseHas('components', ['id' => $sub->id, 'parent_id' => null]);
    }

    public function test_api_index_excludes_subcomponents_by_default(): void
    {
        $parent = Component::factory()->create();
        Component::factory()->withParent($parent)->create();

        $this->getJson('/api/v1/components')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_api_show_includes_subcomponents_and_parent_id(): void
    {
        $parent = Component::factory()->create();
        $sub = Component::factory()->withParent($parent)->create();

        $response = $this->getJson("/api/v1/components/{$parent->id}");
        $response->assertOk()
            ->assertJsonPath('data.parent_id', null)
            ->assertJsonCount(1, 'data.subcomponents');

        $subResponse = $this->getJson("/api/v1/components/{$sub->id}");
        $subResponse->assertOk()
            ->assertJsonPath('data.parent_id', $parent->id);
    }

    public function test_api_parent_id_filter_returns_children(): void
    {
        $parent = Component::factory()->create();
        Component::factory()->withParent($parent)->count(2)->create();
        Component::factory()->create(); // unrelated root

        $this->getJson("/api/v1/components?parent_id={$parent->id}&include_subcomponents=1")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_cannot_nest_subcomponent_under_another_subcomponent(): void
    {
        $parent = Component::factory()->create();
        $sub = Component::factory()->withParent($parent)->create();

        $this->post(route('components.store'), [
            'name' => 'Grandchild',
            'type' => ComponentType::Application->value,
            'parent_id' => $sub->id,
        ])->assertSessionHasErrors(['parent_id']);
    }

    public function test_cannot_set_component_as_its_own_parent(): void
    {
        $component = Component::factory()->create();

        $this->put(route('components.update', $component), [
            'name' => $component->name,
            'type' => $component->type,
            'parent_id' => $component->id,
        ])->assertSessionHasErrors(['parent_id']);
    }

    public function test_cannot_assign_nonexistent_parent_id(): void
    {
        $this->post(route('components.store'), [
            'name' => 'Test Component',
            'type' => ComponentType::Application->value,
            'parent_id' => 99999,
        ])->assertSessionHasErrors(['parent_id']);
    }
}

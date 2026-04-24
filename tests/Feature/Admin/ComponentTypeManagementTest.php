<?php

namespace Tests\Feature\Admin;

use App\Models\Component;
use App\Models\ComponentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_admin_can_view_component_type_index(): void
    {
        $this->get(route('admin.component-types.index'))
            ->assertOk()
            ->assertSee('Application')
            ->assertSee('Manage →');
    }

    public function test_admin_can_create_custom_component_type(): void
    {
        $this->post(route('admin.component-types.store'), [
            'name' => 'Service Mesh',
            'color' => 'indigo',
        ])->assertRedirect(route('admin.component-types.index'));

        $this->assertDatabaseHas('component_types', [
            'name' => 'Service Mesh',
            'color' => 'indigo',
            'is_system' => false,
        ]);
    }

    public function test_custom_type_name_must_be_unique(): void
    {
        $this->post(route('admin.component-types.store'), [
            'name' => 'Application',
            'color' => 'blue',
        ])->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_custom_component_type(): void
    {
        $type = ComponentType::factory()->create(['name' => 'Old Type', 'color' => 'gray', 'is_system' => false]);

        $this->patch(route('admin.component-types.update', $type), [
            'name' => 'New Type',
            'color' => 'teal',
        ])->assertRedirect(route('admin.component-types.show', $type));

        $this->assertDatabaseHas('component_types', ['id' => $type->id, 'name' => 'New Type', 'color' => 'teal']);
    }

    public function test_admin_can_update_system_type(): void
    {
        $type = ComponentType::query()->where('is_system', true)->first();

        $this->patch(route('admin.component-types.update', $type), [
            'name' => $type->name,
            'color' => 'sky',
        ])->assertRedirect(route('admin.component-types.show', $type));

        $this->assertDatabaseHas('component_types', ['id' => $type->id, 'color' => 'sky']);
    }

    public function test_admin_can_delete_custom_type_with_no_components(): void
    {
        $type = ComponentType::factory()->create(['is_system' => false]);

        $this->delete(route('admin.component-types.destroy', $type))
            ->assertRedirect(route('admin.component-types.index'));

        $this->assertDatabaseMissing('component_types', ['id' => $type->id]);
    }

    public function test_cannot_delete_custom_type_in_use(): void
    {
        $type = ComponentType::factory()->create(['name' => 'Custom Type', 'is_system' => false]);
        Component::factory()->create(['type' => $type->name]);

        $this->delete(route('admin.component-types.destroy', $type))
            ->assertRedirect(route('admin.component-types.index'))
            ->assertSessionHasErrors('type');
    }

    public function test_admin_can_delete_unused_system_type(): void
    {
        $type = ComponentType::factory()->create(['is_system' => true]);

        $this->delete(route('admin.component-types.destroy', $type))
            ->assertRedirect(route('admin.component-types.index'));

        $this->assertDatabaseMissing('component_types', ['id' => $type->id]);
    }

    public function test_editor_cannot_create_component_type(): void
    {
        $this->actingAsEditor();

        $this->post(route('admin.component-types.store'), [
            'name' => 'New Type',
            'color' => 'blue',
        ])->assertForbidden();
    }

    public function test_new_custom_type_is_available_when_creating_components(): void
    {
        ComponentType::factory()->create(['name' => 'Service Mesh', 'is_system' => false]);

        $this->get(route('components.create'))->assertOk()->assertSee('Service Mesh');
    }

    public function test_admin_can_view_show_page(): void
    {
        $type = ComponentType::factory()->create();

        $this->get(route('admin.component-types.show', $type))
            ->assertOk()
            ->assertSee($type->name)
            ->assertSee('Allowed Relationship Targets');
    }

    public function test_admin_can_set_relationship_rules(): void
    {
        $source = ComponentType::factory()->create();
        $target = ComponentType::factory()->create();

        $this->patch(
            route('admin.component-types.relationship-rules.update', $source),
            ['allowed_type_ids' => [$target->id]]
        )->assertRedirect(route('admin.component-types.show', $source));

        $this->assertDatabaseHas('component_type_relationship_rules', [
            'source_type_id' => $source->id,
            'target_type_id' => $target->id,
        ]);
    }

    public function test_clearing_relationship_rules_allows_all(): void
    {
        $source = ComponentType::factory()->create();
        $target = ComponentType::factory()->create();
        $source->allowedTargetTypes()->sync([$target->id]);

        $this->patch(
            route('admin.component-types.relationship-rules.update', $source),
            ['allowed_type_ids' => []]
        )->assertRedirect(route('admin.component-types.show', $source));

        $this->assertDatabaseMissing('component_type_relationship_rules', [
            'source_type_id' => $source->id,
        ]);
    }

    public function test_can_use_new_color_sky(): void
    {
        $this->post(route('admin.component-types.store'), [
            'name' => 'Sky Type',
            'color' => 'sky',
        ])->assertRedirect(route('admin.component-types.index'));

        $this->assertDatabaseHas('component_types', ['name' => 'Sky Type', 'color' => 'sky']);
    }
}

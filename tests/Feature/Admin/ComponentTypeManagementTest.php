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
            ->assertSee('System type');
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
        ])->assertRedirect(route('admin.component-types.index'));

        $this->assertDatabaseHas('component_types', ['id' => $type->id, 'name' => 'New Type', 'color' => 'teal']);
    }

    public function test_cannot_update_system_type(): void
    {
        $type = ComponentType::query()->where('is_system', true)->first();

        $this->patch(route('admin.component-types.update', $type), [
            'name' => 'Hacked',
            'color' => 'gray',
        ])->assertForbidden();
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

    public function test_cannot_delete_system_type(): void
    {
        $type = ComponentType::query()->where('is_system', true)->first();

        $this->delete(route('admin.component-types.destroy', $type))->assertForbidden();
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
}

<?php

namespace Tests\Feature\Auth;

use App\Enums\ComponentType;
use App\Enums\FactFieldType;
use App\Models\Component;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_cannot_create_component(): void
    {
        $this->actingAsViewer();

        $this->post(route('components.store'), [
            'name' => 'Test',
            'type' => ComponentType::Application->value,
        ])->assertForbidden();
    }

    public function test_editor_can_create_component(): void
    {
        $this->actingAsEditor();

        $this->post(route('components.store'), [
            'name' => 'Editor App',
            'type' => ComponentType::Application->value,
        ])->assertRedirect();

        $this->assertDatabaseHas('components', ['name' => 'Editor App']);
    }

    public function test_editor_cannot_create_fact_definition(): void
    {
        $this->actingAsEditor();

        $this->post(route('attributes.store'), [
            'name' => 'My Fact',
            'field_type' => FactFieldType::Text->value,
        ])->assertForbidden();
    }

    public function test_admin_can_create_fact_definition(): void
    {
        $this->actingAsAdmin();

        $this->post(route('attributes.store'), [
            'name' => 'My Fact',
            'field_type' => FactFieldType::Text->value,
        ])->assertRedirect();

        $this->assertDatabaseHas('attributes', ['name' => 'My Fact']);
    }

    public function test_viewer_can_view_components(): void
    {
        $this->actingAsViewer();

        Component::factory()->create();

        $this->get(route('components.index'))->assertOk();
    }

    public function test_inactive_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->actingAs($user);

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}

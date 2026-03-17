<?php

namespace Tests\Feature;

use App\Enums\ComponentType;
use App\Models\Component;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_component_with_owner_id_saves_it(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $owner = User::factory()->create();

        $this->post(route('components.store'), [
            'name' => 'Owned Component',
            'type' => ComponentType::Application->value,
            'owner_id' => $owner->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('components', [
            'name' => 'Owned Component',
            'owner_id' => $owner->id,
        ]);
    }

    public function test_index_with_mine_filter_returns_only_authenticated_users_components(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        Component::factory()->create(['name' => 'My Component', 'owner_id' => $user->id]);
        Component::factory()->create(['name' => 'Other Component', 'owner_id' => null]);

        $this->get(route('components.index', ['mine' => '1']))
            ->assertOk()
            ->assertSee('My Component')
            ->assertDontSee('Other Component');
    }

    public function test_index_with_mine_filter_excludes_other_users_components(): void
    {
        $user = User::factory()->admin()->create();
        $other = User::factory()->create();
        $this->actingAs($user);

        Component::factory()->create(['name' => 'My Component', 'owner_id' => $user->id]);
        Component::factory()->create(['name' => 'Their Component', 'owner_id' => $other->id]);

        $this->get(route('components.index', ['mine' => '1']))
            ->assertOk()
            ->assertSee('My Component')
            ->assertDontSee('Their Component');
    }

    public function test_show_page_displays_owner_name(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $owner = User::factory()->create(['name' => 'Jane Smith']);
        $component = Component::factory()->create(['owner_id' => $owner->id]);

        $this->get(route('components.show', $component))
            ->assertOk()
            ->assertSee('Jane Smith');
    }

    public function test_show_page_displays_unassigned_when_no_owner(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $component = Component::factory()->create(['owner_id' => null]);

        $this->get(route('components.show', $component))
            ->assertOk()
            ->assertSee('Unassigned');
    }
}

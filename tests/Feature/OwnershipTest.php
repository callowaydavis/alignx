<?php

namespace Tests\Feature;

use App\Enums\ComponentType;
use App\Models\Component;
use App\Models\Team;
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

        $team = Team::factory()->create();

        $this->post(route('components.store'), [
            'name' => 'Owned Component',
            'type' => ComponentType::Application->value,
            'owner_id' => $team->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('components', [
            'name' => 'Owned Component',
            'owner_id' => $team->id,
        ]);
    }

    public function test_index_with_mine_filter_returns_only_components_owned_by_users_teams(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $myTeam = Team::factory()->create();
        $user->teams()->attach($myTeam);

        Component::factory()->create(['name' => 'My Component', 'owner_id' => $myTeam->id]);
        Component::factory()->create(['name' => 'Other Component', 'owner_id' => null]);

        $this->get(route('components.index', ['mine' => '1']))
            ->assertOk()
            ->assertSee('My Component')
            ->assertDontSee('Other Component');
    }

    public function test_index_with_mine_filter_excludes_other_teams_components(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $myTeam = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user->teams()->attach($myTeam);

        Component::factory()->create(['name' => 'My Component', 'owner_id' => $myTeam->id]);
        Component::factory()->create(['name' => 'Their Component', 'owner_id' => $otherTeam->id]);

        $this->get(route('components.index', ['mine' => '1']))
            ->assertOk()
            ->assertSee('My Component')
            ->assertDontSee('Their Component');
    }

    public function test_show_page_displays_owning_team_name(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $team = Team::factory()->create(['name' => 'Platform Team']);
        $component = Component::factory()->create(['owner_id' => $team->id]);

        $this->get(route('components.show', $component))
            ->assertOk()
            ->assertSee('Platform Team');
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

<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    // --- Index ---

    public function test_any_authenticated_user_can_view_teams_index(): void
    {
        $this->actingAsViewer();
        Team::factory()->count(3)->create();

        $this->get(route('teams.index'))->assertOk()->assertViewIs('teams.index');
    }

    public function test_teams_index_requires_authentication(): void
    {
        $this->get(route('teams.index'))->assertRedirect(route('login'));
    }

    // --- Create ---

    public function test_admin_can_access_team_create_page(): void
    {
        $this->actingAsAdmin();

        $this->get(route('teams.create'))->assertOk()->assertViewIs('teams.create');
    }

    public function test_non_admin_cannot_access_team_create_page(): void
    {
        $this->actingAsEditor();

        $this->get(route('teams.create'))->assertForbidden();
    }

    // --- Store ---

    public function test_admin_can_create_a_team(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('teams.store'), [
            'name' => 'Platform Team',
            'ad_group' => 'GRP_PLATFORM',
            'description' => 'Owns platform services.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('teams', [
            'name' => 'Platform Team',
            'ad_group' => 'GRP_PLATFORM',
        ]);
    }

    public function test_admin_can_create_a_team_without_ad_group(): void
    {
        $this->actingAsAdmin();

        $this->post(route('teams.store'), ['name' => 'No Group Team'])
            ->assertRedirect();

        $this->assertDatabaseHas('teams', ['name' => 'No Group Team', 'ad_group' => null]);
    }

    public function test_non_admin_cannot_create_a_team(): void
    {
        $this->actingAsEditor();

        $this->post(route('teams.store'), ['name' => 'Platform Team'])->assertForbidden();
    }

    public function test_team_name_is_required(): void
    {
        $this->actingAsAdmin();

        $this->post(route('teams.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_ad_group_must_be_unique(): void
    {
        $this->actingAsAdmin();
        Team::factory()->create(['ad_group' => 'GRP_TAKEN']);

        $this->post(route('teams.store'), ['name' => 'Another Team', 'ad_group' => 'GRP_TAKEN'])
            ->assertSessionHasErrors('ad_group');
    }

    // --- Show ---

    public function test_any_authenticated_user_can_view_a_team(): void
    {
        $this->actingAsViewer();
        $team = Team::factory()->create();
        $team->users()->attach(User::factory()->create());

        $this->get(route('teams.show', $team))->assertOk()->assertViewIs('teams.show');
    }

    // --- Edit / Update ---

    public function test_admin_can_edit_a_team(): void
    {
        $this->actingAsAdmin();
        $team = Team::factory()->create(['name' => 'Old Name']);

        $this->get(route('teams.edit', $team))->assertOk();

        $this->put(route('teams.update', $team), ['name' => 'New Name'])
            ->assertRedirect(route('teams.show', $team));

        $this->assertDatabaseHas('teams', ['id' => $team->id, 'name' => 'New Name']);
    }

    public function test_non_admin_cannot_edit_a_team(): void
    {
        $this->actingAsEditor();
        $team = Team::factory()->create();

        $this->get(route('teams.edit', $team))->assertForbidden();
        $this->put(route('teams.update', $team), ['name' => 'Hacked'])->assertForbidden();
    }

    public function test_ad_group_unique_rule_ignores_current_team_on_update(): void
    {
        $this->actingAsAdmin();
        $team = Team::factory()->create(['ad_group' => 'GRP_SAME']);

        $this->put(route('teams.update', $team), ['name' => $team->name, 'ad_group' => 'GRP_SAME'])
            ->assertSessionHasNoErrors();
    }

    // --- Destroy ---

    public function test_admin_can_delete_a_team(): void
    {
        $this->actingAsAdmin();
        $team = Team::factory()->create();

        $this->delete(route('teams.destroy', $team))
            ->assertRedirect(route('teams.index'));

        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }

    public function test_non_admin_cannot_delete_a_team(): void
    {
        $this->actingAsEditor();
        $team = Team::factory()->create();

        $this->delete(route('teams.destroy', $team))->assertForbidden();
    }

    public function test_deleting_a_team_nullifies_component_owner(): void
    {
        $this->actingAsAdmin();
        $team = Team::factory()->create();
        $component = Component::factory()->create(['owner_id' => $team->id]);

        $this->delete(route('teams.destroy', $team));

        $this->assertNull($component->fresh()->owner_id);
    }

    // --- Member management ---

    public function test_admin_can_add_a_member_to_a_team(): void
    {
        $this->actingAsAdmin();
        $team = Team::factory()->create();
        $user = User::factory()->create();

        $this->post(route('teams.members.add', $team), ['user_id' => $user->id])
            ->assertRedirect(route('teams.show', $team));

        $this->assertDatabaseHas('team_user', ['team_id' => $team->id, 'user_id' => $user->id]);
    }

    public function test_non_admin_cannot_add_a_member(): void
    {
        $this->actingAsEditor();
        $team = Team::factory()->create();
        $user = User::factory()->create();

        $this->post(route('teams.members.add', $team), ['user_id' => $user->id])->assertForbidden();
    }

    public function test_adding_existing_member_does_not_duplicate(): void
    {
        $this->actingAsAdmin();
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $team->users()->attach($user);

        $this->post(route('teams.members.add', $team), ['user_id' => $user->id])
            ->assertRedirect();

        $this->assertCount(1, $team->fresh()->users);
    }

    public function test_admin_can_remove_a_member_from_a_team(): void
    {
        $this->actingAsAdmin();
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $team->users()->attach($user);

        $this->delete(route('teams.members.remove', [$team, $user]))
            ->assertRedirect(route('teams.show', $team));

        $this->assertDatabaseMissing('team_user', ['team_id' => $team->id, 'user_id' => $user->id]);
    }

    public function test_non_admin_cannot_remove_a_member(): void
    {
        $this->actingAsEditor();
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $team->users()->attach($user);

        $this->delete(route('teams.members.remove', [$team, $user]))->assertForbidden();
    }

    // --- Component ownership ---

    public function test_component_can_be_assigned_to_a_team(): void
    {
        $this->actingAsAdmin();
        $team = Team::factory()->create();

        $this->post(route('components.store'), [
            'name' => 'My Service',
            'type' => 'Application',
            'owner_id' => $team->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('components', ['name' => 'My Service', 'owner_id' => $team->id]);
    }

    public function test_my_teams_filter_shows_components_owned_by_users_teams(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $myTeam = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user->teams()->attach($myTeam);

        $myComponent = Component::factory()->create(['name' => 'My Component', 'owner_id' => $myTeam->id]);
        $otherComponent = Component::factory()->create(['name' => 'Other Component', 'owner_id' => $otherTeam->id]);

        $this->get(route('components.index', ['mine' => 1]))
            ->assertSee('My Component')
            ->assertDontSee('Other Component');
    }
}

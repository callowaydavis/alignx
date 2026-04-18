<?php

namespace Tests\Feature;

use App\Enums\FactFieldType;
use App\Enums\FactSheetConditionOperator;
use App\Models\Component;
use App\Models\ComponentType;
use App\Models\FactDefinition;
use App\Models\FactSheet;
use App\Models\Team;
use App\Models\User;
use App\Services\FactSheetResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequiredFactsTest extends TestCase
{
    use RefreshDatabase;

    private function makeComponentType(string $name): ComponentType
    {
        return ComponentType::query()->firstOrCreate(['name' => $name], ['color' => 'gray']);
    }

    // --- Admin CRUD ---

    public function test_admin_can_view_fact_sheets_index(): void
    {
        $this->actingAsAdmin();
        FactSheet::factory()->count(3)->create();

        $this->get(route('admin.fact-sheets.index'))
            ->assertOk()
            ->assertViewIs('admin.fact-sheets.index');
    }

    public function test_non_admin_cannot_create_fact_sheet(): void
    {
        $this->actingAsEditor();

        $this->post(route('admin.fact-sheets.store'), ['name' => 'Test Sheet', 'field_type' => 'text'])
            ->assertForbidden();
    }

    public function test_admin_can_create_fact_sheet(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.fact-sheets.store'), [
            'name' => 'Risk Sheet',
            'description' => 'For risk assessment',
        ])->assertRedirect();

        $this->assertDatabaseHas('fact_sheets', ['name' => 'Risk Sheet']);
    }

    public function test_admin_can_create_fact_sheet_scoped_to_component_type(): void
    {
        $this->actingAsAdmin();
        $type = $this->makeComponentType('Application');

        $this->post(route('admin.fact-sheets.store'), [
            'name' => 'App Sheet',
            'component_type_ids' => [$type->id],
        ])->assertRedirect();

        $sheet = FactSheet::query()->where('name', 'App Sheet')->firstOrFail();
        $this->assertTrue($sheet->componentTypes->contains($type));
    }

    public function test_admin_can_restrict_fact_sheet_to_role(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.fact-sheets.store'), [
            'name' => 'Admin Only Sheet',
            'allowed_roles' => ['admin'],
        ])->assertRedirect();

        $sheet = FactSheet::query()->where('name', 'Admin Only Sheet')->firstOrFail();
        $this->assertEquals(['admin'], $sheet->allowed_roles);
    }

    public function test_admin_can_restrict_fact_sheet_to_team(): void
    {
        $this->actingAsAdmin();
        $team = Team::factory()->create();

        $this->post(route('admin.fact-sheets.store'), [
            'name' => 'Risk Sheet',
            'team_ids' => [$team->id],
        ])->assertRedirect();

        $sheet = FactSheet::query()->where('name', 'Risk Sheet')->firstOrFail();
        $this->assertTrue($sheet->teams->contains($team));
    }

    public function test_admin_can_update_fact_sheet(): void
    {
        $this->actingAsAdmin();
        $sheet = FactSheet::factory()->create(['name' => 'Old Name']);

        $this->put(route('admin.fact-sheets.update', $sheet), [
            'name' => 'New Name',
        ])->assertRedirect();

        $this->assertDatabaseHas('fact_sheets', ['id' => $sheet->id, 'name' => 'New Name']);
    }

    public function test_admin_can_delete_fact_sheet(): void
    {
        $this->actingAsAdmin();
        $sheet = FactSheet::factory()->create();

        $this->delete(route('admin.fact-sheets.destroy', $sheet))
            ->assertRedirect(route('admin.fact-sheets.index'));

        $this->assertDatabaseMissing('fact_sheets', ['id' => $sheet->id]);
    }

    // --- Field management ---

    public function test_admin_can_add_definition_to_fact_sheet(): void
    {
        $this->actingAsAdmin();
        $sheet = FactSheet::factory()->create();
        $def = FactDefinition::factory()->create(['name' => 'Server Name', 'field_type' => FactFieldType::Text->value]);

        $this->post(route('admin.fact-sheets.definitions.add', $sheet), [
            'fact_definition_id' => $def->id,
            'is_required' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('fact_sheet_fact_definition', [
            'fact_sheet_id' => $sheet->id,
            'fact_definition_id' => $def->id,
            'is_required' => 1,
        ]);
    }

    public function test_admin_can_remove_definition_from_fact_sheet(): void
    {
        $this->actingAsAdmin();
        $sheet = FactSheet::factory()->create();
        $def = FactDefinition::factory()->create();
        $sheet->factDefinitions()->attach($def->id, ['is_required' => false, 'sort_order' => 0]);

        $this->delete(route('admin.fact-sheets.definitions.remove', [$sheet, $def]))
            ->assertRedirect();

        $this->assertDatabaseMissing('fact_sheet_fact_definition', [
            'fact_sheet_id' => $sheet->id,
            'fact_definition_id' => $def->id,
        ]);
    }

    public function test_admin_can_toggle_required_on_definition(): void
    {
        $this->actingAsAdmin();
        $sheet = FactSheet::factory()->create();
        $def = FactDefinition::factory()->create();
        $sheet->factDefinitions()->attach($def->id, ['is_required' => false, 'sort_order' => 0]);

        $this->patch(route('admin.fact-sheets.definitions.update', [$sheet, $def]), [
            'is_required' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('fact_sheet_fact_definition', [
            'fact_sheet_id' => $sheet->id,
            'fact_definition_id' => $def->id,
            'is_required' => 1,
        ]);
    }

    // --- Conditions ---

    public function test_admin_can_add_condition_to_fact_sheet(): void
    {
        $this->actingAsAdmin();
        $sheet = FactSheet::factory()->create();
        $def = FactDefinition::factory()->create();

        $this->post(route('admin.fact-sheets.conditions.add', $sheet), [
            'fact_definition_id' => $def->id,
            'operator' => FactSheetConditionOperator::Equals->value,
            'value' => 'Production',
        ])->assertRedirect();

        $this->assertDatabaseHas('fact_sheet_conditions', [
            'fact_sheet_id' => $sheet->id,
            'fact_definition_id' => $def->id,
            'operator' => 'equals',
            'value' => 'Production',
        ]);
    }

    public function test_admin_can_remove_condition_from_fact_sheet(): void
    {
        $this->actingAsAdmin();
        $sheet = FactSheet::factory()->create();
        $def = FactDefinition::factory()->create();
        $condition = $sheet->conditions()->create([
            'fact_definition_id' => $def->id,
            'operator' => FactSheetConditionOperator::Equals->value,
            'value' => 'Production',
        ]);

        $this->delete(route('admin.fact-sheets.conditions.remove', [$sheet, $condition]))
            ->assertRedirect();

        $this->assertDatabaseMissing('fact_sheet_conditions', ['id' => $condition->id]);
    }

    // --- isAccessibleBy ---

    public function test_unrestricted_sheet_is_accessible_by_all_users(): void
    {
        $sheet = FactSheet::factory()->create(['allowed_roles' => null]);
        $user = User::factory()->editor()->create();

        $this->assertTrue($sheet->isAccessibleBy($user));
    }

    public function test_role_restricted_sheet_allows_matching_role(): void
    {
        $sheet = FactSheet::factory()->create(['allowed_roles' => ['admin']]);
        $admin = User::factory()->admin()->create();

        $this->assertTrue($sheet->isAccessibleBy($admin));
    }

    public function test_role_restricted_sheet_blocks_non_matching_role(): void
    {
        $sheet = FactSheet::factory()->create(['allowed_roles' => ['admin']]);
        $sheet->load('teams');
        $editor = User::factory()->editor()->create();

        $this->assertFalse($sheet->isAccessibleBy($editor));
    }

    public function test_team_restricted_sheet_allows_team_member(): void
    {
        $team = Team::factory()->create();
        $sheet = FactSheet::factory()->create(['allowed_roles' => null]);
        $sheet->teams()->attach($team);
        $sheet->load('teams');

        $user = User::factory()->editor()->create();
        $team->users()->attach($user);
        $user->load('teams');

        $this->assertTrue($sheet->isAccessibleBy($user));
    }

    public function test_team_restricted_sheet_blocks_non_team_member(): void
    {
        $team = Team::factory()->create();
        $sheet = FactSheet::factory()->create(['allowed_roles' => null]);
        $sheet->teams()->attach($team);
        $sheet->load('teams');

        $user = User::factory()->editor()->create();

        $this->assertFalse($sheet->isAccessibleBy($user));
    }

    // --- Component fact sheet submission ---

    public function test_user_can_submit_fact_sheet_for_component(): void
    {
        $user = User::factory()->editor()->create();
        $this->actingAs($user);

        $component = Component::factory()->create(['type' => 'Application']);
        $sheet = FactSheet::factory()->create(['allowed_roles' => null]);
        $def = FactDefinition::factory()->create(['name' => 'Hosting Provider', 'field_type' => FactFieldType::Text->value]);
        $sheet->factDefinitions()->attach($def->id, ['is_required' => false, 'sort_order' => 0]);

        $this->post(route('components.fact-sheets.submit', [$component, $sheet]), [
            'facts' => [$def->id => 'AWS'],
        ])->assertRedirect(route('components.show', $component));

        $this->assertDatabaseHas('component_facts', [
            'component_id' => $component->id,
            'fact_definition_id' => $def->id,
            'value' => 'AWS',
        ]);
    }

    public function test_required_field_in_fact_sheet_fails_when_missing(): void
    {
        $user = User::factory()->editor()->create();
        $this->actingAs($user);

        $component = Component::factory()->create();
        $sheet = FactSheet::factory()->create(['allowed_roles' => null]);
        $def = FactDefinition::factory()->create(['name' => 'Server Name', 'field_type' => FactFieldType::Text->value]);
        $sheet->factDefinitions()->attach($def->id, ['is_required' => true, 'sort_order' => 0]);

        $this->post(route('components.fact-sheets.submit', [$component, $sheet]), [])
            ->assertSessionHasErrors("facts.{$def->id}");
    }

    public function test_forbidden_when_user_cannot_access_sheet(): void
    {
        $team = Team::factory()->create();
        $sheet = FactSheet::factory()->create(['allowed_roles' => null]);
        $sheet->teams()->attach($team);

        $user = User::factory()->editor()->create(); // not in team
        $this->actingAs($user);
        $component = Component::factory()->create();

        $this->post(route('components.fact-sheets.submit', [$component, $sheet]), [])
            ->assertForbidden();
    }

    // --- Conditional sheet visibility (FactSheetResolver) ---

    public function test_conditional_sheet_shown_when_condition_matches(): void
    {
        $conditionDef = FactDefinition::factory()->create(['name' => 'Environment', 'field_type' => FactFieldType::Text->value]);
        $sheet = FactSheet::factory()->create(['allowed_roles' => null]);
        $sheet->conditions()->create([
            'fact_definition_id' => $conditionDef->id,
            'operator' => FactSheetConditionOperator::Equals->value,
            'value' => 'Production',
        ]);

        $component = Component::factory()->create(['type' => 'Application']);
        $component->facts()->create(['fact_definition_id' => $conditionDef->id, 'value' => 'Production']);

        $user = User::factory()->editor()->create();

        $sheets = FactSheetResolver::forComponent($component, $user);
        $this->assertTrue($sheets->contains('id', $sheet->id));
    }

    public function test_conditional_sheet_hidden_when_condition_does_not_match(): void
    {
        $conditionDef = FactDefinition::factory()->create(['name' => 'Environment', 'field_type' => FactFieldType::Text->value]);
        $sheet = FactSheet::factory()->create(['allowed_roles' => null]);
        $sheet->conditions()->create([
            'fact_definition_id' => $conditionDef->id,
            'operator' => FactSheetConditionOperator::Equals->value,
            'value' => 'Production',
        ]);

        $component = Component::factory()->create(['type' => 'Application']);
        $component->facts()->create(['fact_definition_id' => $conditionDef->id, 'value' => 'Staging']);

        $user = User::factory()->editor()->create();

        $sheets = FactSheetResolver::forComponent($component, $user);
        $this->assertFalse($sheets->contains('id', $sheet->id));
    }
}

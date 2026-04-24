<?php

namespace Tests\Feature;

use App\Enums\FactFieldType;
use App\Enums\LifecycleStage;
use App\Enums\TodoStatus;
use App\Models\Attribute;
use App\Models\Component;
use App\Models\ComponentTodo;
use App\Models\ComponentType;
use App\Models\FactSheet;
use App\Models\Team;
use App\Services\ComponentHealthScore;
use App\Services\FactSheetResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentHealthScoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    private function makeType(string $name): ComponentType
    {
        return ComponentType::query()->firstOrCreate(['name' => $name], ['color' => 'gray']);
    }

    private function makeRequiredFact(string $typeName, string $factName = 'Required Fact'): Attribute
    {
        $type = $this->makeType($typeName);
        $def = Attribute::factory()->create([
            'name' => $factName,
            'field_type' => FactFieldType::Text->value,
        ]);

        $sheet = FactSheet::factory()->create(['name' => "Sheet for {$factName}"]);
        $sheet->componentTypes()->attach($type->id);
        $sheet->attributes()->attach($def->id, ['is_required' => true, 'sort_order' => 0]);

        return $def;
    }

    private function perfectComponent(): Component
    {
        $team = Team::factory()->create();
        $this->makeType('Application');

        return Component::factory()->create([
            'type' => 'Application',
            'description' => 'A well-documented component.',
            'lifecycle_stage' => LifecycleStage::Active->value,
            'owner_id' => $team->id,
        ]);
    }

    // --- Perfect score ---

    public function test_perfect_component_scores_100(): void
    {
        $component = $this->perfectComponent();
        $hs = ComponentHealthScore::for($component);

        $this->assertSame(100, $hs->score());
        $this->assertSame('healthy', $hs->rating());
    }

    // --- Description deduction ---

    public function test_no_description_deducts_10(): void
    {
        $component = $this->perfectComponent();
        $component->update(['description' => null]);

        $hs = ComponentHealthScore::for($component);

        $this->assertSame(90, $hs->score());
    }

    // --- Lifecycle stage deduction ---

    public function test_no_lifecycle_stage_deducts_20(): void
    {
        $component = $this->perfectComponent();
        $component->update(['lifecycle_stage' => null]);

        $hs = ComponentHealthScore::for($component);

        $this->assertSame(80, $hs->score());
    }

    // --- Owner deduction ---

    public function test_no_owner_deducts_15(): void
    {
        $component = $this->perfectComponent();
        $component->update(['owner_id' => null]);

        $hs = ComponentHealthScore::for($component);

        $this->assertSame(85, $hs->score());
    }

    // --- Required facts deductions ---

    public function test_one_missing_required_fact_deducts_10(): void
    {
        $this->makeType('Application');
        $this->makeRequiredFact('Application', 'Fact A');

        $component = $this->perfectComponent();
        $hs = ComponentHealthScore::for($component);

        $this->assertSame(90, $hs->score());
    }

    public function test_two_missing_required_facts_deducts_20(): void
    {
        $this->makeType('Application');
        $this->makeRequiredFact('Application', 'Fact A');
        $this->makeRequiredFact('Application', 'Fact B');

        $component = $this->perfectComponent();
        $hs = ComponentHealthScore::for($component);

        $this->assertSame(80, $hs->score());
    }

    public function test_missing_required_facts_capped_at_20(): void
    {
        $this->makeType('Application');
        $this->makeRequiredFact('Application', 'Fact A');
        $this->makeRequiredFact('Application', 'Fact B');
        $this->makeRequiredFact('Application', 'Fact C');

        $component = $this->perfectComponent();
        $hs = ComponentHealthScore::for($component);

        $this->assertSame(80, $hs->score());
    }

    public function test_fulfilling_required_fact_removes_deduction(): void
    {
        $this->makeType('Application');
        $def = $this->makeRequiredFact('Application', 'Fact A');

        $component = $this->perfectComponent();
        $component->facts()->create(['attribute_id' => $def->id, 'value' => 'filled']);

        $hs = ComponentHealthScore::for($component);

        $this->assertSame(100, $hs->score());
    }

    public function test_required_facts_for_other_types_not_counted(): void
    {
        $this->makeType('Application');
        $this->makeType('IT Component');
        $this->makeRequiredFact('IT Component', 'Server Name');

        $component = $this->perfectComponent();
        $hs = ComponentHealthScore::for($component);

        $this->assertSame(100, $hs->score());
    }

    // --- Open to-do deductions ---

    public function test_one_open_todo_deducts_5(): void
    {
        $component = $this->perfectComponent();
        ComponentTodo::factory()->create(['component_id' => $component->id, 'status' => TodoStatus::Pending->value]);

        $hs = ComponentHealthScore::for($component);

        $this->assertSame(95, $hs->score());
    }

    public function test_three_open_todos_deducts_15(): void
    {
        $component = $this->perfectComponent();
        ComponentTodo::factory()->count(3)->create(['component_id' => $component->id, 'status' => TodoStatus::Pending->value]);

        $hs = ComponentHealthScore::for($component);

        $this->assertSame(85, $hs->score());
    }

    public function test_open_todos_capped_at_15(): void
    {
        $component = $this->perfectComponent();
        ComponentTodo::factory()->count(5)->create(['component_id' => $component->id, 'status' => TodoStatus::Pending->value]);

        $hs = ComponentHealthScore::for($component);

        $this->assertSame(85, $hs->score());
    }

    public function test_completed_todos_do_not_deduct(): void
    {
        $component = $this->perfectComponent();
        ComponentTodo::factory()->create(['component_id' => $component->id, 'status' => TodoStatus::Completed->value]);

        $hs = ComponentHealthScore::for($component);

        $this->assertSame(100, $hs->score());
    }

    // --- Overdue to-do deductions ---

    public function test_one_overdue_todo_deducts_additional_5(): void
    {
        $component = $this->perfectComponent();
        ComponentTodo::factory()->create([
            'component_id' => $component->id,
            'status' => TodoStatus::Pending->value,
            'due_date' => today()->subDay(),
        ]);

        $hs = ComponentHealthScore::for($component);

        // -5 open + -5 overdue = 90
        $this->assertSame(90, $hs->score());
    }

    public function test_two_overdue_todos_deducts_additional_10(): void
    {
        $component = $this->perfectComponent();
        ComponentTodo::factory()->count(2)->create([
            'component_id' => $component->id,
            'status' => TodoStatus::Pending->value,
            'due_date' => today()->subDay(),
        ]);

        $hs = ComponentHealthScore::for($component);

        // -10 open (2*5) + -10 overdue (2*5, max 10) = 80
        $this->assertSame(80, $hs->score());
    }

    public function test_overdue_todos_capped_at_10(): void
    {
        $component = $this->perfectComponent();
        ComponentTodo::factory()->count(4)->create([
            'component_id' => $component->id,
            'status' => TodoStatus::Pending->value,
            'due_date' => today()->subDay(),
        ]);

        $hs = ComponentHealthScore::for($component);

        // -15 open (4*5, max 15) + -10 overdue (4*5, max 10) = 75
        $this->assertSame(75, $hs->score());
    }

    public function test_todo_due_today_is_not_overdue(): void
    {
        $component = $this->perfectComponent();
        ComponentTodo::factory()->create([
            'component_id' => $component->id,
            'status' => TodoStatus::Pending->value,
            'due_date' => today(),
        ]);

        $hs = ComponentHealthScore::for($component);

        // -5 open only, no overdue deduction = 95
        $this->assertSame(95, $hs->score());
    }

    // --- Floor at 0 ---

    public function test_score_does_not_go_below_zero(): void
    {
        $this->makeType('Application');
        $this->makeRequiredFact('Application', 'Fact A');
        $this->makeRequiredFact('Application', 'Fact B');
        $this->makeRequiredFact('Application', 'Fact C');

        $component = Component::factory()->create([
            'type' => 'Application',
            'description' => null,
            'lifecycle_stage' => null,
            'owner_id' => null,
        ]);

        ComponentTodo::factory()->count(5)->create([
            'component_id' => $component->id,
            'status' => TodoStatus::Pending->value,
            'due_date' => today()->subDay(),
        ]);

        $hs = ComponentHealthScore::for($component);

        $this->assertGreaterThanOrEqual(0, $hs->score());
    }

    // --- Rating thresholds ---

    public function test_score_80_is_healthy(): void
    {
        $component = $this->perfectComponent();
        $component->update(['lifecycle_stage' => null]);

        $hs = ComponentHealthScore::for($component);

        $this->assertSame(80, $hs->score());
        $this->assertSame('healthy', $hs->rating());
    }

    public function test_score_79_is_at_risk(): void
    {
        $component = $this->perfectComponent();
        $component->update(['description' => null, 'lifecycle_stage' => null]);
        // 100 - 10 - 20 = 70 -> at_risk

        $hs = ComponentHealthScore::for($component);

        $this->assertSame(70, $hs->score());
        $this->assertSame('at_risk', $hs->rating());
    }

    public function test_score_50_is_at_risk(): void
    {
        $component = $this->perfectComponent();
        $component->update(['description' => null, 'lifecycle_stage' => null, 'owner_id' => null]);
        // 100 - 10 - 20 - 15 = 55 -> at_risk

        $hs = ComponentHealthScore::for($component);

        $this->assertSame(55, $hs->score());
        $this->assertSame('at_risk', $hs->rating());
    }

    public function test_score_49_is_critical(): void
    {
        $this->makeType('Application');
        $this->makeRequiredFact('Application', 'Fact A');
        $this->makeRequiredFact('Application', 'Fact B');

        $component = Component::factory()->create([
            'type' => 'Application',
            'description' => null,
            'lifecycle_stage' => null,
            'owner_id' => null,
        ]);

        ComponentTodo::factory()->count(3)->create([
            'component_id' => $component->id,
            'status' => TodoStatus::Pending->value,
        ]);

        // 100 - 10 - 20 - 15 - 20 (2 facts) - 15 (3 todos) = 20 -> critical
        $hs = ComponentHealthScore::for($component);

        $this->assertSame('critical', $hs->rating());
    }

    // --- Breakdown array ---

    public function test_breakdown_contains_ok_status_for_all_present_fields(): void
    {
        $component = $this->perfectComponent();
        $hs = ComponentHealthScore::for($component);

        $statuses = array_column($hs->breakdown(), 'status');

        $this->assertNotContains('bad', $statuses);
        $this->assertNotContains('warn', $statuses);
    }

    public function test_breakdown_contains_bad_status_for_missing_description(): void
    {
        $component = $this->perfectComponent();
        $component->update(['description' => null]);

        $hs = ComponentHealthScore::for($component);
        $labels = array_column($hs->breakdown(), 'label');

        $this->assertContains('No description', $labels);
    }

    // --- Batch (withRequiredFacts) ---

    public function test_with_required_facts_produces_same_score_as_for(): void
    {
        $this->makeType('Application');
        $def = $this->makeRequiredFact('Application');

        $component = $this->perfectComponent();
        $component->load(['owner', 'facts', 'todos']);

        // Collect required fact definitions the same way the index page does
        $requiredFactDefs = FactSheetResolver::forComponentType($component->type)
            ->flatMap(fn ($sheet) => $sheet->attributes->filter(fn ($d) => $d->pivot->is_required))
            ->unique('id');

        $hsBatch = ComponentHealthScore::withRequiredFacts($component, $requiredFactDefs);
        $hsSingle = ComponentHealthScore::for($component);

        $this->assertSame($hsSingle->score(), $hsBatch->score());
        $this->assertSame($hsSingle->rating(), $hsBatch->rating());
    }

    // --- Integration: show page renders score ---

    public function test_show_page_displays_health_score(): void
    {
        $component = $this->perfectComponent();

        $this->get(route('components.show', $component))
            ->assertOk()
            ->assertSee('Health Score');
    }

    // --- Integration: index page renders health column ---

    public function test_index_page_displays_health_column(): void
    {
        Component::factory()->create(['type' => 'Application']);

        $this->get(route('components.index'))
            ->assertOk()
            ->assertSee('Health');
    }
}

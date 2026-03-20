<?php

namespace Tests\Feature;

use App\Enums\FactFieldType;
use App\Models\Component;
use App\Models\ComponentType;
use App\Models\FactDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequiredFactsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsEditor();
    }

    private function makeType(string $name): ComponentType
    {
        return ComponentType::query()->firstOrCreate(['name' => $name], ['color' => 'gray']);
    }

    private function makeRequiredFact(string $typeName, string $factName = 'Server Name'): FactDefinition
    {
        return FactDefinition::factory()->create([
            'name' => $factName,
            'field_type' => FactFieldType::Text->value,
            'required_for_types' => [$typeName],
        ]);
    }

    // --- Store validation ---

    public function test_creating_component_without_required_fact_fails(): void
    {
        $this->makeType('IT Component');
        $this->makeRequiredFact('IT Component', 'Server Name');

        $this->post(route('components.store'), [
            'name' => 'My Server',
            'type' => 'IT Component',
        ])->assertSessionHasErrors();

        $this->assertDatabaseMissing('components', ['name' => 'My Server']);
    }

    public function test_creating_component_with_required_fact_succeeds(): void
    {
        $this->makeType('IT Component');
        $def = $this->makeRequiredFact('IT Component', 'Server Name');

        $this->post(route('components.store'), [
            'name' => 'My Server',
            'type' => 'IT Component',
            'required_facts' => [$def->id => 'PROD-SRV-01'],
        ])->assertRedirect();

        $component = Component::query()->where('name', 'My Server')->firstOrFail();

        $this->assertDatabaseHas('component_facts', [
            'component_id' => $component->id,
            'fact_definition_id' => $def->id,
            'value' => 'PROD-SRV-01',
        ]);
    }

    public function test_multiple_required_facts_all_must_be_provided(): void
    {
        $this->makeType('IT Component');
        $def1 = $this->makeRequiredFact('IT Component', 'Server Name');
        $def2 = $this->makeRequiredFact('IT Component', 'Operating System');

        $response = $this->post(route('components.store'), [
            'name' => 'My Server',
            'type' => 'IT Component',
            'required_facts' => [$def1->id => 'PROD-SRV-01'],
            // missing def2
        ]);

        $response->assertSessionHasErrors("required_facts.{$def2->id}");
        $this->assertDatabaseMissing('components', ['name' => 'My Server']);
    }

    public function test_required_facts_only_enforced_for_specified_type(): void
    {
        $this->makeType('Application');
        $this->makeType('IT Component');
        $this->makeRequiredFact('IT Component', 'Server Name');

        $this->post(route('components.store'), [
            'name' => 'My App',
            'type' => 'Application',
        ])->assertRedirect();

        $this->assertDatabaseHas('components', ['name' => 'My App']);
    }

    public function test_required_fact_creates_fact_record_on_component(): void
    {
        $this->makeType('IT Component');
        $def = $this->makeRequiredFact('IT Component', 'Operating System');

        $this->post(route('components.store'), [
            'name' => 'Web Server',
            'type' => 'IT Component',
            'required_facts' => [$def->id => 'Ubuntu 22.04'],
        ])->assertRedirect();

        $component = Component::query()->where('name', 'Web Server')->firstOrFail();
        $this->assertCount(1, $component->facts);
        $this->assertEquals('Ubuntu 22.04', $component->facts->first()->value);
    }

    // --- Model helpers ---

    public function test_is_required_for_type_returns_true_when_type_matches(): void
    {
        $def = FactDefinition::factory()->create([
            'required_for_types' => ['IT Component'],
        ]);

        $this->assertTrue($def->isRequiredForType('IT Component'));
        $this->assertFalse($def->isRequiredForType('Application'));
    }

    public function test_is_required_for_type_returns_false_when_no_required_types(): void
    {
        $def = FactDefinition::factory()->create(['required_for_types' => null]);

        $this->assertFalse($def->isRequiredForType('IT Component'));
    }

    // --- Fact definition form ---

    public function test_fact_definition_can_be_created_with_required_for_types(): void
    {
        $this->actingAsAdmin();
        $this->makeType('IT Component');

        $this->post(route('fact-definitions.store'), [
            'name' => 'Server Name',
            'field_type' => FactFieldType::Text->value,
            'required_for_types' => ['IT Component'],
        ])->assertRedirect();

        $this->assertDatabaseHas('fact_definitions', ['name' => 'Server Name']);
        $def = FactDefinition::query()->where('name', 'Server Name')->first();
        $this->assertEquals(['IT Component'], $def->required_for_types);
    }

    public function test_fact_definition_required_for_types_can_be_updated(): void
    {
        $this->actingAsAdmin();
        $this->makeType('IT Component');
        $this->makeType('Application');

        $def = FactDefinition::factory()->create([
            'required_for_types' => ['IT Component'],
        ]);

        $this->put(route('fact-definitions.update', $def), [
            'name' => $def->name,
            'field_type' => $def->field_type->value,
            'required_for_types' => ['Application'],
        ])->assertRedirect();

        $this->assertEquals(['Application'], $def->fresh()->required_for_types);
    }

    // --- Show page warning ---

    public function test_show_page_displays_missing_required_facts_warning(): void
    {
        $this->makeType('IT Component');
        $this->makeRequiredFact('IT Component', 'Server Name');

        $component = Component::factory()->create(['type' => 'IT Component']);

        $this->get(route('components.show', $component))
            ->assertOk()
            ->assertSee('Missing required facts')
            ->assertSee('Server Name');
    }

    public function test_show_page_does_not_show_warning_when_all_required_facts_present(): void
    {
        $this->makeType('IT Component');
        $def = $this->makeRequiredFact('IT Component', 'Server Name');

        $component = Component::factory()->create(['type' => 'IT Component']);
        $component->facts()->create([
            'fact_definition_id' => $def->id,
            'value' => 'PROD-SRV-01',
        ]);

        $this->get(route('components.show', $component))
            ->assertOk()
            ->assertDontSee('Missing required facts');
    }
}

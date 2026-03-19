<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ComponentRelationship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagramTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_show_page_renders_diagrams_tab(): void
    {
        $component = Component::factory()->create();

        $response = $this->get(route('components.show', $component));

        $response->assertStatus(200);
        $response->assertSee('Diagrams');
        $response->assertSee('Overview');
    }

    public function test_diagrams_tab_shows_empty_state_when_no_relationships(): void
    {
        $component = Component::factory()->create();

        $response = $this->get(route('components.show', $component));

        $response->assertStatus(200);
        $response->assertSee('No relationships to diagram');
    }

    public function test_diagrams_tab_renders_diagram_controls_when_relationships_exist(): void
    {
        $source = Component::factory()->create(['name' => 'Source App']);
        $target = Component::factory()->create(['name' => 'Target App']);

        ComponentRelationship::factory()->create([
            'source_component_id' => $source->id,
            'target_component_id' => $target->id,
            'relationship_type' => 'Uses',
        ]);

        $response = $this->get(route('components.show', $source));

        $response->assertStatus(200);
        $response->assertSee('Network Map');
        $response->assertSee('Dependency View');
        $response->assertSee('Landscape');
    }

    public function test_graph_data_json_is_embedded_with_correct_structure(): void
    {
        $source = Component::factory()->create(['name' => 'My App']);
        $target = Component::factory()->create(['name' => 'Their App']);

        ComponentRelationship::factory()->create([
            'source_component_id' => $source->id,
            'target_component_id' => $target->id,
            'relationship_type' => 'Uses',
        ]);

        $response = $this->get(route('components.show', $source));

        $response->assertStatus(200);
        $response->assertSee('graph-data');
        $response->assertSee('My App');
        $response->assertSee('Their App');
        $response->assertSee('Uses');
    }

    public function test_landscape_view_groups_related_components_by_type(): void
    {
        $source = Component::factory()->create();
        $target = Component::factory()->create();

        ComponentRelationship::factory()->create([
            'source_component_id' => $source->id,
            'target_component_id' => $target->id,
            'relationship_type' => 'Depends On',
        ]);

        $response = $this->get(route('components.show', $source));

        $response->assertStatus(200);
        // The landscape view should show the target component's type group
        $response->assertSee($target->type);
        $response->assertSee($target->name);
    }

    public function test_graph_data_includes_both_incoming_and_outgoing_relationships(): void
    {
        $focal = Component::factory()->create(['name' => 'Focal']);
        $upstream = Component::factory()->create(['name' => 'Upstream']);
        $downstream = Component::factory()->create(['name' => 'Downstream']);

        ComponentRelationship::factory()->create([
            'source_component_id' => $upstream->id,
            'target_component_id' => $focal->id,
            'relationship_type' => 'Provides',
        ]);
        ComponentRelationship::factory()->create([
            'source_component_id' => $focal->id,
            'target_component_id' => $downstream->id,
            'relationship_type' => 'Feeds',
        ]);

        $response = $this->get(route('components.show', $focal));

        $response->assertStatus(200);
        $response->assertSee('Upstream');
        $response->assertSee('Downstream');
        $response->assertSee('Provides');
        $response->assertSee('Feeds');
    }

    public function test_graph_data_includes_transitive_relationships(): void
    {
        $app = Component::factory()->create(['name' => 'Application']);
        $db = Component::factory()->create(['name' => 'Database']);
        $server = Component::factory()->create(['name' => 'Server']);

        // App -> Database -> Server (two hops from App)
        ComponentRelationship::factory()->create([
            'source_component_id' => $app->id,
            'target_component_id' => $db->id,
            'relationship_type' => 'Uses',
        ]);
        ComponentRelationship::factory()->create([
            'source_component_id' => $db->id,
            'target_component_id' => $server->id,
            'relationship_type' => 'Runs On',
        ]);

        $response = $this->get(route('components.show', $app));

        $response->assertStatus(200);
        $response->assertSee('Database');
        $response->assertSee('Server');
        $response->assertSee('Runs On');
    }

    public function test_graph_traversal_does_not_infinite_loop_on_circular_relationships(): void
    {
        $a = Component::factory()->create(['name' => 'Component A']);
        $b = Component::factory()->create(['name' => 'Component B']);

        ComponentRelationship::factory()->create([
            'source_component_id' => $a->id,
            'target_component_id' => $b->id,
        ]);
        ComponentRelationship::factory()->create([
            'source_component_id' => $b->id,
            'target_component_id' => $a->id,
        ]);

        $this->get(route('components.show', $a))->assertStatus(200);
    }
}

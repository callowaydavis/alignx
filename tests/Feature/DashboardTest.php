<?php

namespace Tests\Feature;

use App\Enums\ComponentType;
use App\Enums\LifecycleStage;
use App\Models\Component;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_dashboard_loads_successfully(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
    }

    public function test_dashboard_shows_total_component_count(): void
    {
        Component::factory()->count(3)->create();

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('3');
    }

    public function test_dashboard_shows_counts_by_type(): void
    {
        Component::factory()->count(2)->create(['type' => ComponentType::Application->value]);
        Component::factory()->create(['type' => ComponentType::Process->value]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Application');
        $response->assertSee('Process');
    }

    public function test_dashboard_shows_recently_updated_components(): void
    {
        $component = Component::factory()->create(['name' => 'My Recent App']);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('My Recent App');
    }

    public function test_dashboard_shows_lifecycle_distribution(): void
    {
        Component::factory()->create(['lifecycle_stage' => LifecycleStage::Active->value]);
        Component::factory()->create(['lifecycle_stage' => LifecycleStage::Plan->value]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Active');
        $response->assertSee('Plan');
    }

    public function test_dashboard_shows_top_tags(): void
    {
        $tag = Tag::query()->create(['name' => 'critical']);
        $component = Component::factory()->create();
        $component->tags()->attach($tag);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('critical');
    }

    public function test_dashboard_empty_state_shows_create_cta(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('No components yet.');
    }

    public function test_root_route_resolves_to_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\ComponentType;
use App\Models\Component;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    // --- Web routes ---

    public function test_create_component_with_tags(): void
    {
        $response = $this->post(route('components.store'), [
            'name' => 'My App',
            'type' => ComponentType::Application->value,
            'tags' => ['cloud', 'critical'],
        ]);

        $response->assertRedirect();

        $component = Component::query()->where('name', 'My App')->first();
        $this->assertNotNull($component);
        $this->assertCount(2, $component->tags);
        $this->assertTrue($component->tags->contains('name', 'cloud'));
        $this->assertTrue($component->tags->contains('name', 'critical'));
    }

    public function test_create_component_without_tags(): void
    {
        $response = $this->post(route('components.store'), [
            'name' => 'My App',
            'type' => ComponentType::Application->value,
        ]);

        $response->assertRedirect();

        $component = Component::query()->where('name', 'My App')->first();
        $this->assertNotNull($component);
        $this->assertCount(0, $component->tags);
    }

    public function test_tag_name_too_long_fails_validation(): void
    {
        $response = $this->post(route('components.store'), [
            'name' => 'My App',
            'type' => ComponentType::Application->value,
            'tags' => [str_repeat('a', 51)],
        ]);

        $response->assertSessionHasErrors('tags.*');
    }

    public function test_update_component_syncs_tags(): void
    {
        $component = Component::factory()->create();
        Tag::query()->create(['name' => 'old-tag']);
        $component->tags()->attach(Tag::query()->where('name', 'old-tag')->first());

        $response = $this->put(route('components.update', $component), [
            'name' => $component->name,
            'type' => $component->type->value,
            'tags' => ['new-tag'],
        ]);

        $response->assertRedirect();

        $component->refresh();
        $this->assertCount(1, $component->tags);
        $this->assertTrue($component->tags->contains('name', 'new-tag'));
        $this->assertFalse($component->tags->contains('name', 'old-tag'));
    }

    public function test_update_component_with_empty_tags_removes_all(): void
    {
        $component = Component::factory()->create();
        $tag = Tag::query()->create(['name' => 'existing']);
        $component->tags()->attach($tag);

        $response = $this->put(route('components.update', $component), [
            'name' => $component->name,
            'type' => $component->type->value,
        ]);

        $response->assertRedirect();

        $component->refresh();
        $this->assertCount(0, $component->tags);
    }

    public function test_index_filters_by_tag(): void
    {
        $tagA = Tag::query()->create(['name' => 'cloud']);
        $tagB = Tag::query()->create(['name' => 'legacy']);
        $compA = Component::factory()->create(['name' => 'Cloud App']);
        $compB = Component::factory()->create(['name' => 'Legacy App']);
        $compA->tags()->attach($tagA);
        $compB->tags()->attach($tagB);

        $response = $this->get(route('components.index', ['tag' => 'cloud']));

        $response->assertStatus(200);
        $response->assertSee('Cloud App');
        $response->assertDontSee('Legacy App');
    }

    public function test_tags_shown_on_show_page(): void
    {
        $component = Component::factory()->create();
        $tag = Tag::query()->create(['name' => 'finance']);
        $component->tags()->attach($tag);

        $response = $this->get(route('components.show', $component));

        $response->assertStatus(200);
        $response->assertSee('finance');
    }

    public function test_tags_reuse_existing_tag_record(): void
    {
        Tag::query()->create(['name' => 'shared']);

        $this->post(route('components.store'), [
            'name' => 'App 1',
            'type' => ComponentType::Application->value,
            'tags' => ['shared'],
        ]);

        $this->post(route('components.store'), [
            'name' => 'App 2',
            'type' => ComponentType::Application->value,
            'tags' => ['shared'],
        ]);

        $this->assertEquals(1, Tag::query()->where('name', 'shared')->count());
    }

    // --- API routes ---

    public function test_api_list_tags(): void
    {
        Tag::query()->create(['name' => 'alpha']);
        Tag::query()->create(['name' => 'beta']);

        $response = $this->getJson('/api/v1/tags');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_api_create_tag(): void
    {
        $response = $this->postJson('/api/v1/tags', ['name' => 'new-tag']);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'new-tag');
        $this->assertDatabaseHas('tags', ['name' => 'new-tag']);
    }

    public function test_api_create_duplicate_tag_fails(): void
    {
        Tag::query()->create(['name' => 'existing']);

        $response = $this->postJson('/api/v1/tags', ['name' => 'existing']);

        $response->assertStatus(422);
    }

    public function test_api_create_component_with_tags(): void
    {
        $response = $this->postJson('/api/v1/components', [
            'name' => 'API App',
            'type' => ComponentType::Application->value,
            'tags' => ['cloud', 'critical'],
        ]);

        $response->assertStatus(201);
        $response->assertJsonCount(2, 'data.tags');
    }

    public function test_api_filter_components_by_tag(): void
    {
        $tagA = Tag::query()->create(['name' => 'cloud']);
        $compA = Component::factory()->create(['name' => 'Cloud App']);
        $compB = Component::factory()->create(['name' => 'Other App']);
        $compA->tags()->attach($tagA);

        $response = $this->getJson('/api/v1/components?tag=cloud');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Cloud App');
    }

    public function test_api_component_resource_includes_tags(): void
    {
        $component = Component::factory()->create();
        $tag = Tag::query()->create(['name' => 'finance']);
        $component->tags()->attach($tag);

        $response = $this->getJson("/api/v1/components/{$component->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['tags']]);
        $response->assertJsonCount(1, 'data.tags');
        $response->assertJsonPath('data.tags.0.name', 'finance');
    }
}

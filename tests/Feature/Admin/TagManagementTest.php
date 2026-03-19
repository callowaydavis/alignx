<?php

namespace Tests\Feature\Admin;

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_admin_can_view_tag_index(): void
    {
        Tag::factory()->create(['name' => 'Infrastructure']);

        $this->get(route('admin.tags.index'))->assertOk()->assertSee('Infrastructure');
    }

    public function test_admin_can_create_tag(): void
    {
        $this->post(route('admin.tags.store'), ['name' => 'Security'])
            ->assertRedirect(route('admin.tags.index'));

        $this->assertDatabaseHas('tags', ['name' => 'Security']);
    }

    public function test_create_tag_name_must_be_unique(): void
    {
        Tag::factory()->create(['name' => 'Existing']);

        $this->post(route('admin.tags.store'), ['name' => 'Existing'])
            ->assertSessionHasErrors('name');
    }

    public function test_admin_can_rename_tag(): void
    {
        $tag = Tag::factory()->create(['name' => 'Old Name']);

        $this->patch(route('admin.tags.update', $tag), ['name' => 'New Name'])
            ->assertRedirect(route('admin.tags.index'));

        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'New Name']);
    }

    public function test_admin_can_delete_tag(): void
    {
        $tag = Tag::factory()->create();

        $this->delete(route('admin.tags.destroy', $tag))
            ->assertRedirect(route('admin.tags.index'));

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_editor_cannot_create_tag(): void
    {
        $this->actingAsEditor();

        $this->post(route('admin.tags.store'), ['name' => 'New Tag'])
            ->assertForbidden();
    }

    public function test_viewer_cannot_delete_tag(): void
    {
        $tag = Tag::factory()->create();
        $this->actingAsViewer();

        $this->delete(route('admin.tags.destroy', $tag))->assertForbidden();
    }
}

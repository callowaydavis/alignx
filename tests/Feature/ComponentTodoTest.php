<?php

namespace Tests\Feature;

use App\Enums\TodoCategory;
use App\Enums\TodoStatus;
use App\Models\Component;
use App\Models\ComponentTodo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentTodoTest extends TestCase
{
    use RefreshDatabase;

    protected Component $component;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsEditor();
        $this->component = Component::factory()->create();
    }

    // --- Store ---

    public function test_can_add_a_todo(): void
    {
        $this->post(route('components.todos.store', $this->component), [
            'condition' => 'Ensure TLS is enabled',
            'category' => TodoCategory::Security->value,
            'status' => TodoStatus::Pending->value,
        ])->assertRedirect(route('components.show', $this->component).'#todos');

        $this->assertDatabaseHas('component_todos', [
            'component_id' => $this->component->id,
            'condition' => 'Ensure TLS is enabled',
            'category' => TodoCategory::Security->value,
            'status' => TodoStatus::Pending->value,
        ]);
    }

    public function test_can_add_todo_with_all_fields(): void
    {
        $user = User::factory()->create();

        $this->post(route('components.todos.store', $this->component), [
            'condition' => 'Review documentation',
            'category' => TodoCategory::Documentation->value,
            'status' => TodoStatus::InProgress->value,
            'accepted_by' => $user->id,
            'acceptance_notes' => 'Needs sign-off from CTO',
            'due_date' => '2026-12-31',
        ])->assertRedirect();

        $this->assertDatabaseHas('component_todos', [
            'component_id' => $this->component->id,
            'condition' => 'Review documentation',
            'accepted_by' => $user->id,
            'acceptance_notes' => 'Needs sign-off from CTO',
        ]);
    }

    public function test_store_requires_condition(): void
    {
        $this->post(route('components.todos.store', $this->component), [
            'category' => TodoCategory::Security->value,
            'status' => TodoStatus::Pending->value,
        ])->assertSessionHasErrors('condition');
    }

    public function test_store_requires_valid_category(): void
    {
        $this->post(route('components.todos.store', $this->component), [
            'condition' => 'Some condition',
            'category' => 'Invalid',
            'status' => TodoStatus::Pending->value,
        ])->assertSessionHasErrors('category');
    }

    public function test_store_requires_valid_status(): void
    {
        $this->post(route('components.todos.store', $this->component), [
            'condition' => 'Some condition',
            'category' => TodoCategory::Security->value,
            'status' => 'Invalid',
        ])->assertSessionHasErrors('status');
    }

    public function test_viewer_cannot_add_todo(): void
    {
        $this->actingAsViewer();

        $this->post(route('components.todos.store', $this->component), [
            'condition' => 'Some condition',
            'category' => TodoCategory::Security->value,
            'status' => TodoStatus::Pending->value,
        ])->assertForbidden();
    }

    // --- Completion stamp ---

    public function test_completing_a_todo_stamps_completed_by_and_at(): void
    {
        $todo = ComponentTodo::factory()->create([
            'component_id' => $this->component->id,
            'status' => TodoStatus::Pending->value,
        ]);

        $user = auth()->user();

        $this->patch(route('components.todos.update', [$this->component, $todo]), [
            'condition' => $todo->condition,
            'category' => $todo->category->value,
            'status' => TodoStatus::Completed->value,
        ])->assertRedirect();

        $todo->refresh();

        $this->assertEquals(TodoStatus::Completed, $todo->status);
        $this->assertEquals($user->id, $todo->completed_by);
        $this->assertNotNull($todo->completed_at);
    }

    public function test_already_completed_todo_does_not_reset_completion_stamp(): void
    {
        $originalUser = User::factory()->create();
        $originalTime = now()->subDay()->startOfSecond();

        $todo = ComponentTodo::factory()->create([
            'component_id' => $this->component->id,
            'status' => TodoStatus::Completed->value,
            'completed_by' => $originalUser->id,
            'completed_at' => $originalTime,
        ]);

        $this->patch(route('components.todos.update', [$this->component, $todo]), [
            'condition' => $todo->condition,
            'category' => $todo->category->value,
            'status' => TodoStatus::Completed->value,
        ])->assertRedirect();

        $todo->refresh();

        $this->assertEquals($originalUser->id, $todo->completed_by);
        $this->assertEquals($originalTime->toDateTimeString(), $todo->completed_at->toDateTimeString());
    }

    public function test_moving_todo_away_from_completed_clears_stamp(): void
    {
        $todo = ComponentTodo::factory()->completed()->create([
            'component_id' => $this->component->id,
            'completed_by' => auth()->id(),
        ]);

        $this->patch(route('components.todos.update', [$this->component, $todo]), [
            'condition' => $todo->condition,
            'category' => $todo->category->value,
            'status' => TodoStatus::Pending->value,
        ])->assertRedirect();

        $todo->refresh();

        $this->assertNull($todo->completed_by);
        $this->assertNull($todo->completed_at);
    }

    // --- Update ---

    public function test_can_update_a_todo(): void
    {
        $todo = ComponentTodo::factory()->create([
            'component_id' => $this->component->id,
            'condition' => 'Old condition',
        ]);

        $this->patch(route('components.todos.update', [$this->component, $todo]), [
            'condition' => 'Updated condition',
            'category' => TodoCategory::Compliance->value,
            'status' => TodoStatus::InProgress->value,
        ])->assertRedirect(route('components.show', $this->component).'#todos');

        $this->assertDatabaseHas('component_todos', [
            'id' => $todo->id,
            'condition' => 'Updated condition',
            'category' => TodoCategory::Compliance->value,
        ]);
    }

    // --- Destroy ---

    public function test_can_delete_a_todo(): void
    {
        $todo = ComponentTodo::factory()->create(['component_id' => $this->component->id]);

        $this->delete(route('components.todos.destroy', [$this->component, $todo]))
            ->assertRedirect(route('components.show', $this->component).'#todos');

        $this->assertDatabaseMissing('component_todos', ['id' => $todo->id]);
    }

    public function test_viewer_cannot_delete_todo(): void
    {
        $this->actingAsViewer();

        $todo = ComponentTodo::factory()->create(['component_id' => $this->component->id]);

        $this->delete(route('components.todos.destroy', [$this->component, $todo]))
            ->assertForbidden();

        $this->assertDatabaseHas('component_todos', ['id' => $todo->id]);
    }

    // --- Show page ---

    public function test_todos_appear_on_component_show_page(): void
    {
        ComponentTodo::factory()->create([
            'component_id' => $this->component->id,
            'condition' => 'Ensure backups are configured',
            'category' => TodoCategory::Operational->value,
            'status' => TodoStatus::Pending->value,
        ]);

        $this->get(route('components.show', $this->component))
            ->assertOk()
            ->assertSee('Ensure backups are configured')
            ->assertSee('Operational')
            ->assertSee('Pending');
    }

    public function test_show_page_displays_completed_by_info(): void
    {
        $completedBy = User::factory()->create(['name' => 'Jane Doe']);

        ComponentTodo::factory()->completed()->create([
            'component_id' => $this->component->id,
            'completed_by' => $completedBy->id,
        ]);

        $this->get(route('components.show', $this->component))
            ->assertOk()
            ->assertSee('Jane Doe');
    }

    public function test_cascades_when_component_deleted(): void
    {
        ComponentTodo::factory()->count(3)->create(['component_id' => $this->component->id]);

        $this->assertDatabaseCount('component_todos', 3);

        $this->component->delete();

        $this->assertDatabaseCount('component_todos', 0);
    }
}

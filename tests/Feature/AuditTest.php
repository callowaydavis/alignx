<?php

namespace Tests\Feature;

use App\Enums\ComponentType;
use App\Models\Audit;
use App\Models\Component;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_component_records_created_audit(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $this->post(route('components.store'), [
            'name' => 'Audited App',
            'type' => ComponentType::Application->value,
        ])->assertRedirect();

        $component = Component::query()->where('name', 'Audited App')->first();

        $this->assertDatabaseHas('audits', [
            'auditable_type' => Component::class,
            'auditable_id' => $component->id,
            'event' => 'created',
            'user_id' => $user->id,
        ]);
    }

    public function test_updating_a_component_records_updated_audit_with_old_and_new_values(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $component = Component::factory()->create(['name' => 'Old Name']);

        $this->put(route('components.update', $component), [
            'name' => 'New Name',
            'type' => $component->type->value,
        ])->assertRedirect();

        $audit = Audit::query()
            ->where('auditable_type', Component::class)
            ->where('auditable_id', $component->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals('Old Name', $audit->old_values['name']);
        $this->assertEquals('New Name', $audit->new_values['name']);
        $this->assertEquals($user->id, $audit->user_id);
    }

    public function test_deleting_a_component_records_deleted_audit(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $component = Component::factory()->create();
        $componentId = $component->id;

        $this->delete(route('components.destroy', $component))->assertRedirect();

        $this->assertDatabaseHas('audits', [
            'auditable_type' => Component::class,
            'auditable_id' => $componentId,
            'event' => 'deleted',
        ]);
    }

    public function test_history_tab_renders_audit_entries_on_show_page(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $component = Component::factory()->create();

        Audit::create([
            'auditable_type' => Component::class,
            'auditable_id' => $component->id,
            'event' => 'created',
            'user_id' => $user->id,
        ]);

        $this->get(route('components.show', $component))
            ->assertOk()
            ->assertSee('History');
    }

    public function test_activity_index_returns_200_and_lists_audit_records(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $component = Component::factory()->create();

        Audit::create([
            'auditable_type' => Component::class,
            'auditable_id' => $component->id,
            'event' => 'created',
            'user_id' => $user->id,
        ]);

        $this->get(route('activity.index'))
            ->assertOk()
            ->assertViewIs('activity.index');
    }

    public function test_audit_user_id_matches_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $component = Component::factory()->create(['name' => 'Test']);

        $audit = Audit::query()
            ->where('auditable_type', Component::class)
            ->where('auditable_id', $component->id)
            ->where('event', 'created')
            ->first();

        $this->assertEquals($user->id, $audit->user_id);
    }
}

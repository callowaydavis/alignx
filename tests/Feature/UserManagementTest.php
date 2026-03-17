<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_users_index(): void
    {
        $this->actingAsAdmin();

        $this->get(route('users.index'))->assertOk();
    }

    public function test_editor_cannot_access_users_index(): void
    {
        $this->actingAsEditor();

        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_admin_can_create_user(): void
    {
        $this->actingAsAdmin();

        $this->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::Viewer->value,
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    public function test_admin_can_update_user(): void
    {
        $this->actingAsAdmin();

        $user = User::factory()->viewer()->create();

        $this->put(route('users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'role' => UserRole::Editor->value,
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'role' => UserRole::Editor->value,
        ]);
    }

    public function test_admin_can_deactivate_user(): void
    {
        $this->actingAsAdmin();

        $user = User::factory()->viewer()->create();

        $this->delete(route('users.destroy', $user))->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
    }

    public function test_admin_cannot_deactivate_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->delete(route('users.destroy', $admin))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true]);
    }

    public function test_editor_cannot_create_user(): void
    {
        $this->actingAsEditor();

        $this->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::Viewer->value,
        ])->assertForbidden();
    }
}

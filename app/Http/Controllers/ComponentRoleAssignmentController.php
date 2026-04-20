<?php

namespace App\Http\Controllers;

use App\Enums\AssigneeType;
use App\Models\Component;
use App\Models\ComponentRoleAssignment;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ComponentRoleAssignmentController extends Controller
{
    public function store(Request $request, Component $component): RedirectResponse
    {
        $this->authorize('update', $component);

        $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ]);

        $role = Role::query()->find($request->integer('role_id'));

        if (! $role->allow_multiple) {
            $component->roleAssignments()->where('role_id', $role->id)->delete();
        }

        $userId = null;
        $teamId = null;

        if ($role->assignee_type === AssigneeType::User) {
            $userId = $request->integer('user_id') ?: null;
        } elseif ($role->assignee_type === AssigneeType::Team) {
            $teamId = $request->integer('team_id') ?: null;
        } else {
            $userId = $request->integer('user_id') ?: null;
            $teamId = $request->integer('team_id') ?: null;
        }

        $assignment = $component->roleAssignments()->create([
            'role_id' => $role->id,
            'user_id' => $userId,
            'team_id' => $teamId,
        ]);

        $assignment->load(['user', 'team']);
        $assigneeName = $assignment->assigneeName();

        $component->recordAudit('role_assigned', [], [
            'role' => $role->name,
            'assignee' => $assigneeName,
        ]);

        return redirect()->route('components.show', $component)
            ->with('success', "Role '{$role->name}' assigned to {$assigneeName}.");
    }

    public function destroy(Component $component, ComponentRoleAssignment $assignment): RedirectResponse
    {
        $this->authorize('update', $component);

        $assignment->load(['role', 'user', 'team']);
        $roleName = $assignment->role->name;
        $assigneeName = $assignment->assigneeName();

        $component->recordAudit('role_unassigned', [
            'role' => $roleName,
            'assignee' => $assigneeName,
        ], []);

        $assignment->delete();

        return redirect()->route('components.show', $component)
            ->with('success', 'Role assignment removed.');
    }
}

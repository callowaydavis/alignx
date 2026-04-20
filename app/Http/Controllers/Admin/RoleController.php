<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssigneeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\ComponentType;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->with('componentTypes')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        $componentTypes = ComponentType::query()->orderBy('name')->get();
        $assigneeTypes = AssigneeType::cases();

        return view('admin.roles.create', compact('componentTypes', 'assigneeTypes'));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $validated = $request->validated();
        $componentTypeIds = $validated['component_type_ids'] ?? [];
        unset($validated['component_type_ids']);

        $validated['allow_multiple'] = $request->boolean('allow_multiple');
        $validated['is_required'] = $request->boolean('is_required');

        $role = Role::query()->create($validated);
        $role->componentTypes()->sync($componentTypeIds);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        $role->load('componentTypes');
        $componentTypes = ComponentType::query()->orderBy('name')->get();
        $assigneeTypes = AssigneeType::cases();

        return view('admin.roles.edit', compact('role', 'componentTypes', 'assigneeTypes'));
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $validated = $request->validated();
        $componentTypeIds = $validated['component_type_ids'] ?? [];
        unset($validated['component_type_ids']);

        $validated['allow_multiple'] = $request->boolean('allow_multiple');
        $validated['is_required'] = $request->boolean('is_required');

        $role->update($validated);
        $role->componentTypes()->sync($componentTypeIds);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}

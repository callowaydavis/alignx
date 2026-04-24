<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComponentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComponentTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ComponentType::class);

        $types = ComponentType::query()->orderByDesc('is_system')->orderBy('name')->withCount('components')->get();

        return view('admin.component-types.index', compact('types'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ComponentType::class);

        $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:component_types,name'],
            'color' => ['required', 'string', 'in:blue,purple,green,orange,teal,yellow,red,pink,indigo,gray,sky,cyan,lime,emerald,rose,fuchsia,violet,amber,slate'],
        ]);

        ComponentType::query()->create([
            'name' => $request->string('name')->trim(),
            'color' => $request->string('color'),
            'is_system' => false,
        ]);

        return redirect()->route('admin.component-types.index')
            ->with('success', 'Component type created.');
    }

    public function update(Request $request, ComponentType $componentType): RedirectResponse
    {
        $this->authorize('update', $componentType);

        $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:component_types,name,'.$componentType->id],
            'color' => ['required', 'string', 'in:blue,purple,green,orange,teal,yellow,red,pink,indigo,gray,sky,cyan,lime,emerald,rose,fuchsia,violet,amber,slate'],
        ]);

        $componentType->update([
            'name' => $request->string('name')->trim(),
            'color' => $request->string('color'),
        ]);

        return redirect()->route('admin.component-types.show', $componentType)
            ->with('success', 'Component type updated.');
    }

    public function show(ComponentType $componentType): View
    {
        $this->authorize('viewAny', ComponentType::class);

        $componentType->load('allowedTargetTypes');
        $allTypes = ComponentType::query()->orderBy('name')->get();

        return view('admin.component-types.show', compact('componentType', 'allTypes'));
    }

    public function destroy(ComponentType $componentType): RedirectResponse
    {
        $this->authorize('delete', $componentType);

        $count = $componentType->components()->count();

        if ($count > 0) {
            return redirect()->route('admin.component-types.index')
                ->withErrors(['type' => "Cannot delete \"{$componentType->name}\" — it is used by {$count} component(s)."]);
        }

        $componentType->delete();

        return redirect()->route('admin.component-types.index')
            ->with('success', 'Component type deleted.');
    }

    public function updateRelationshipRules(Request $request, ComponentType $componentType): RedirectResponse
    {
        $this->authorize('update', $componentType);

        $request->validate([
            'allowed_type_ids' => ['nullable', 'array'],
            'allowed_type_ids.*' => ['integer', 'exists:component_types,id'],
        ]);

        $ids = collect($request->input('allowed_type_ids', []))
            ->filter(fn ($id) => (int) $id !== $componentType->id)
            ->values()
            ->all();

        $componentType->allowedTargetTypes()->sync($ids);

        return redirect()->route('admin.component-types.show', $componentType)
            ->with('success', 'Relationship rules updated.');
    }
}

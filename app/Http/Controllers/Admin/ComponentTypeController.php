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
            'color' => ['required', 'string', 'in:blue,purple,green,orange,teal,yellow,red,pink,indigo,gray'],
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

        abort_if($componentType->is_system, 403, 'System types cannot be modified.');

        $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:component_types,name,'.$componentType->id],
            'color' => ['required', 'string', 'in:blue,purple,green,orange,teal,yellow,red,pink,indigo,gray'],
        ]);

        $componentType->update([
            'name' => $request->string('name')->trim(),
            'color' => $request->string('color'),
        ]);

        return redirect()->route('admin.component-types.index')
            ->with('success', 'Component type updated.');
    }

    public function destroy(ComponentType $componentType): RedirectResponse
    {
        $this->authorize('delete', $componentType);

        abort_if($componentType->is_system, 403, 'System types cannot be deleted.');

        if ($componentType->components()->exists()) {
            return redirect()->route('admin.component-types.index')
                ->withErrors(['type' => "Cannot delete \"{$componentType->name}\" — it is used by {$componentType->components_count} component(s)."]);
        }

        $componentType->delete();

        return redirect()->route('admin.component-types.index')
            ->with('success', 'Component type deleted.');
    }
}

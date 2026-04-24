<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FactSheetConditionOperator;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFactSheetRequest;
use App\Http\Requests\UpdateFactSheetRequest;
use App\Models\Attribute;
use App\Models\ComponentType;
use App\Models\FactSheet;
use App\Models\FactSheetCondition;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FactSheetController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', FactSheet::class);

        $factSheets = FactSheet::query()
            ->withCount('attributes')
            ->with(['componentTypes', 'teams'])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.fact-sheets.index', compact('factSheets'));
    }

    public function create(): View
    {
        $this->authorize('create', FactSheet::class);

        $componentTypes = ComponentType::query()->orderBy('name')->get();
        $teams = Team::query()->orderBy('name')->get();
        $roles = User::allRoles();

        return view('admin.fact-sheets.create', compact('componentTypes', 'teams', 'roles'));
    }

    public function store(StoreFactSheetRequest $request): RedirectResponse
    {
        $this->authorize('create', FactSheet::class);

        $validated = $request->validated();
        $componentTypeIds = $validated['component_type_ids'] ?? [];
        $teamIds = $validated['team_ids'] ?? [];
        unset($validated['component_type_ids'], $validated['team_ids']);

        $factSheet = FactSheet::query()->create($validated);
        $factSheet->componentTypes()->sync($componentTypeIds);
        $factSheet->teams()->sync($teamIds);

        return redirect()->route('admin.fact-sheets.show', $factSheet)
            ->with('success', 'Fact sheet created successfully.');
    }

    public function show(FactSheet $factSheet): View
    {
        $this->authorize('view', $factSheet);

        $factSheet->load(['attributes', 'componentTypes', 'teams', 'conditions.attribute']);

        $availableDefinitions = Attribute::query()
            ->whereNotIn('id', $factSheet->attributes->pluck('id'))
            ->orderBy('name')
            ->get();

        $allDefinitions = Attribute::query()->orderBy('name')->get();
        $operators = FactSheetConditionOperator::cases();

        return view('admin.fact-sheets.show', compact(
            'factSheet', 'availableDefinitions', 'allDefinitions', 'operators'
        ));
    }

    public function edit(FactSheet $factSheet): View
    {
        $this->authorize('update', $factSheet);

        $factSheet->load(['componentTypes', 'teams']);
        $componentTypes = ComponentType::query()->orderBy('name')->get();
        $teams = Team::query()->orderBy('name')->get();
        $roles = User::allRoles();

        return view('admin.fact-sheets.edit', compact('factSheet', 'componentTypes', 'teams', 'roles'));
    }

    public function update(UpdateFactSheetRequest $request, FactSheet $factSheet): RedirectResponse
    {
        $this->authorize('update', $factSheet);

        $validated = $request->validated();
        $componentTypeIds = $validated['component_type_ids'] ?? [];
        $teamIds = $validated['team_ids'] ?? [];
        unset($validated['component_type_ids'], $validated['team_ids']);

        $factSheet->update($validated);
        $factSheet->componentTypes()->sync($componentTypeIds);
        $factSheet->teams()->sync($teamIds);

        return redirect()->route('admin.fact-sheets.show', $factSheet)
            ->with('success', 'Fact sheet updated successfully.');
    }

    public function destroy(FactSheet $factSheet): RedirectResponse
    {
        $this->authorize('delete', $factSheet);

        $factSheet->delete();

        return redirect()->route('admin.fact-sheets.index')
            ->with('success', 'Fact sheet deleted successfully.');
    }

    // --- Definition management ---

    public function addDefinition(Request $request, FactSheet $factSheet): RedirectResponse
    {
        $this->authorize('update', $factSheet);

        $request->validate([
            'attribute_id' => ['required', 'integer', 'exists:attributes,id'],
            'is_required' => ['boolean'],
        ]);

        $maxSort = $factSheet->attributes()->max('sort_order') ?? -1;

        $factSheet->attributes()->attach($request->integer('attribute_id'), [
            'is_required' => $request->boolean('is_required'),
            'sort_order' => $maxSort + 1,
        ]);

        return back()->with('success', 'Field added to fact sheet.');
    }

    public function removeDefinition(FactSheet $factSheet, Attribute $attribute): RedirectResponse
    {
        $this->authorize('update', $factSheet);

        $factSheet->attributes()->detach($attribute->id);

        return back()->with('success', 'Field removed from fact sheet.');
    }

    public function updateDefinition(Request $request, FactSheet $factSheet, Attribute $attribute): RedirectResponse
    {
        $this->authorize('update', $factSheet);

        $request->validate([
            'is_required' => ['required', 'boolean'],
        ]);

        $factSheet->attributes()->updateExistingPivot($attribute->id, [
            'is_required' => $request->boolean('is_required'),
        ]);

        return back()->with('success', 'Field updated.');
    }

    // --- Condition management ---

    public function addCondition(Request $request, FactSheet $factSheet): RedirectResponse
    {
        $this->authorize('update', $factSheet);

        $request->validate([
            'attribute_id' => ['required', 'integer', 'exists:attributes,id'],
            'operator' => ['required', 'string', 'in:'.implode(',', array_column(FactSheetConditionOperator::cases(), 'value'))],
            'value' => ['nullable', 'string', 'max:255'],
        ]);

        $factSheet->conditions()->create([
            'attribute_id' => $request->integer('attribute_id'),
            'operator' => $request->string('operator')->value(),
            'value' => $request->string('value')->value() ?: null,
        ]);

        return back()->with('success', 'Condition added.');
    }

    public function removeCondition(FactSheet $factSheet, FactSheetCondition $condition): RedirectResponse
    {
        $this->authorize('update', $factSheet);

        $condition->delete();

        return back()->with('success', 'Condition removed.');
    }
}
